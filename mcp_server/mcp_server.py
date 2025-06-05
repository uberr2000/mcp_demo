# -*- coding: utf-8 -*-
from flask import Flask, Response, request, jsonify, make_response
from flask_cors import CORS
import json
import asyncio
from typing import Dict, Any, List
import os
from dotenv import load_dotenv
import requests
from datetime import datetime
import time

load_dotenv()

# Laravel MCP服务器配置
LARAVEL_MCP_URL = os.getenv("LARAVEL_MCP_URL", "http://localhost:8000/mcp")
MCP_SERVER_PORT = int(os.getenv("MCP_SERVER_PORT", "8080"))

app = Flask(__name__)
# 配置 CORS
CORS(
    app,
    resources={
        r"/*": {
            "origins": ["*", "http://localhost:*", "http://127.0.0.1:*"],
            "methods": ["GET", "POST", "OPTIONS"],
            "allow_headers": [
                "*",
                "Content-Type",
                "Authorization",
                "X-Requested-With",
                "Accept",
            ],
            "expose_headers": ["*", "Content-Type", "Authorization"],
            "supports_credentials": True,
            "max_age": 3600,
        }
    },
)

# 缓存工具定义
TOOLS_CACHE = None
TOOLS_CACHE_TIMESTAMP = 0
TOOLS_CACHE_TTL = 300  # 缓存5分钟


def get_tools_from_laravel() -> Dict[str, Any]:
    """从Laravel MCP获取工具定义"""
    global TOOLS_CACHE, TOOLS_CACHE_TIMESTAMP

    current_time = time.time()
    # 如果缓存存在且未过期，直接返回缓存
    if TOOLS_CACHE and (current_time - TOOLS_CACHE_TIMESTAMP) < TOOLS_CACHE_TTL:
        return TOOLS_CACHE

    try:
        response = requests.get(f"{LARAVEL_MCP_URL}/tools")
        response.raise_for_status()
        tools = response.json()
        print("tools from laravel:", tools)  # 调试用

        # 兼容你的格式
        if isinstance(tools, dict) and "tools" in tools:
            # 转换为客户端期望的数组格式
            tools_info = {
                "type": "tools",
                "tools": [
                    {
                        "name": k,
                        "description": v.get("description", ""),
                        "parameters": v.get("inputSchema", {}),
                    }
                    for k, v in tools["tools"].items()
                ],
            }
        else:
            tools_info = {
                "type": "tools",
                "tools": tools if isinstance(tools, list) else [],
            }

        # 更新缓存
        TOOLS_CACHE = tools_info
        TOOLS_CACHE_TIMESTAMP = current_time
        return tools_info
    except requests.exceptions.RequestException as e:
        raise Exception(f"获取工具定义失败: {str(e)}")


def call_laravel_mcp_tool(tool_name: str, arguments: Dict[str, Any]) -> Dict[str, Any]:
    """调用Laravel MCP工具"""
    try:
        response = requests.post(
            f"{LARAVEL_MCP_URL}/{tool_name}",
            json=arguments,
            headers={"Content-Type": "application/json"},
        )
        response.raise_for_status()
        return response.json()
    except requests.exceptions.RequestException as e:
        raise Exception(f"调用Laravel MCP工具失败: {str(e)}")


# 工具函数映射
TOOL_FUNCTIONS = {
    "get_orders": lambda args: call_laravel_mcp_tool("get_orders", args),
    "get_products": lambda args: call_laravel_mcp_tool("get_products", args),
    "get_order_analytics": lambda args: call_laravel_mcp_tool(
        "get_order_analytics", args
    ),
    "get_customer_stats": lambda args: call_laravel_mcp_tool(
        "get_customer_stats", args
    ),
    "send_excel_email": lambda args: call_laravel_mcp_tool("send_excel_email", args),
}


def format_sse(data: str, event: str = None) -> str:
    """格式化SSE消息，确保符合MCP Inspector要求"""
    if isinstance(data, dict):
        data = json.dumps(data, ensure_ascii=False)
    msg = f"data: {data}\n"
    if event is not None:
        msg = f"event: {event}\n{msg}"
    msg += "\n"  # 确保每条消息后有两个换行符
    return msg


@app.route("/", methods=["GET"])
def index():
    """健康检查端点"""
    return jsonify(
        {
            "status": "ok",
            "message": "MCP Server is running",
            "version": "1.0.0",
            "endpoints": {
                "sse": f"http://localhost:{MCP_SERVER_PORT}/sse",
                "mcp_sse": f"http://localhost:{MCP_SERVER_PORT}/mcp/sse",
            },
        }
    )


@app.route("/sse", methods=["GET", "POST", "OPTIONS"])
@app.route("/mcp/sse", methods=["GET", "POST", "OPTIONS"])
def sse():
    """SSE 端点，支持 MCP Inspector"""
    print("\n=== SSE 连接建立 ===")
    print(f"请求路径: {request.path}")
    print(f"请求方法: {request.method}")
    print(f"请求头: {dict(request.headers)}")
    print(f"来源: {request.origin}")

    # 处理 OPTIONS 请求
    if request.method == "OPTIONS":
        response = make_response()
        response.headers.add("Access-Control-Allow-Origin", "*")
        response.headers.add("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        response.headers.add("Access-Control-Allow-Headers", "*")
        response.headers.add("Access-Control-Max-Age", "3600")
        return response

    if request.method == "POST":
        print("收到 POST 请求")
        try:
            data = request.get_json()
            print(f"POST 数据: {data}")
            tool_name = data.get("tool")
            params = data.get("params", {})
            print(f"工具名称: {tool_name}")
            print(f"参数: {params}")

            if tool_name not in TOOL_FUNCTIONS:
                print(f"错误: 未找到工具 {tool_name}")
                return jsonify({"error": f"Tool {tool_name} not found"}), 404

            result = TOOL_FUNCTIONS[tool_name](**params)
            print(f"工具执行结果: {result}")
            return jsonify(result)
        except Exception as e:
            print(f"处理 POST 请求时发生错误: {str(e)}")
            return jsonify({"error": str(e)}), 500

    def event_stream():
        print("\n=== 开始事件流 ===")
        try:
            # 发送初始连接成功消息
            yield format_sse(
                {"status": "connected", "timestamp": datetime.now().isoformat()},
                "connected",
            )

            # 获取工具列表
            tools = get_tools_from_laravel()
            print(
                f"从 Laravel 获取到的工具列表: {json.dumps(tools, ensure_ascii=False, indent=2)}"
            )

            # 发送工具列表
            tools_event = format_sse(tools, "tools")
            print(f"发送工具列表事件: {tools_event}")
            yield tools_event

            # 保持连接活跃
            while True:
                print("发送心跳...")
                heartbeat = format_sse(
                    {
                        "type": "heartbeat",
                        "timestamp": datetime.now().isoformat(),
                        "status": "alive",
                    },
                    "heartbeat",
                )
                yield heartbeat
                time.sleep(30)
        except Exception as e:
            print(f"事件流处理时发生错误: {str(e)}")
            error_event = format_sse(
                {
                    "type": "error",
                    "message": str(e),
                    "timestamp": datetime.now().isoformat(),
                },
                "error",
            )
            print(f"发送错误事件: {error_event}")
            yield error_event

    response = Response(
        event_stream(),
        mimetype="text/event-stream",
        headers={
            "Access-Control-Allow-Origin": "*",
            "Cache-Control": "no-cache",
            "Connection": "keep-alive",
            "X-Accel-Buffering": "no",
            "Content-Type": "text/event-stream; charset=utf-8",
        },
    )
    return response


if __name__ == "__main__":
    print(f"启动 MCP 服务器在端口 {MCP_SERVER_PORT}...")
    print(f"SSE 端点: http://localhost:{MCP_SERVER_PORT}/sse")
    print(f"备用 SSE 端点: http://localhost:{MCP_SERVER_PORT}/mcp/sse")
    print("支持 MCP Inspector 连接")
    app.run(host="0.0.0.0", port=MCP_SERVER_PORT, debug=True)
