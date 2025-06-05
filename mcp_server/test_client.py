# -*- coding: utf-8 -*-
import requests
import json
import sseclient
import time
from datetime import datetime, timedelta
import os
from dotenv import load_dotenv

load_dotenv()

# MCP 服务器配置
MCP_SERVER_PORT = int(os.getenv("MCP_SERVER_PORT", "8080"))
MCP_SERVER_URL = f"http://127.0.0.1:{MCP_SERVER_PORT}"


def connect_to_sse():
    """连接到SSE服务器并获取工具列表"""
    print("\n=== 开始连接 SSE 服务器 ===")
    try:
        response = requests.get(f"{MCP_SERVER_URL}/sse", stream=True)
        response.raise_for_status()
        print("成功连接到 SSE 服务器")

        tools = None
        for line in response.iter_lines():
            if line:
                line = line.decode("utf-8")
                print(f"\n收到原始数据: {line}")

                if line.startswith("event:"):
                    event_type = line[6:].strip()
                    print(f"事件类型: {event_type}")
                    continue

                if line.startswith("data:"):
                    data = line[5:].strip()
                    print(f"事件数据: {data}")

                    if event_type == "tools":
                        try:
                            tools = json.loads(data)
                            print(
                                f"成功解析工具列表: {json.dumps(tools, ensure_ascii=False, indent=2)}"
                            )
                        except json.JSONDecodeError as e:
                            print(f"解析工具列表时发生错误: {str(e)}")
                            print(f"原始数据: {data}")
                    elif event_type == "error":
                        print(f"收到错误事件: {data}")
                    elif event_type == "heartbeat":
                        print(f"收到心跳: {data}")

                if line == "" and tools is not None:
                    print("工具列表接收完成")
                    return tools

        print("连接关闭，未收到工具列表")
        return None
    except Exception as e:
        print(f"连接 SSE 服务器时发生错误: {str(e)}")
        return None


def test_tool(tool_name, params=None):
    """测试单个工具"""
    print(f"\n=== 测试工具: {tool_name} ===")
    if params:
        print(f"参数: {json.dumps(params, ensure_ascii=False, indent=2)}")

    try:
        response = requests.post(
            f"{MCP_SERVER_URL}/sse",
            json={"tool": tool_name, "params": params or {}},
        )
        response.raise_for_status()
        result = response.json()
        print(f"工具执行结果: {json.dumps(result, ensure_ascii=False, indent=2)}")
        return result
    except Exception as e:
        print(f"执行工具时发生错误: {str(e)}")
        return None


def test_mcp_server():
    # 首先获取工具列表
    print("\n=== 开始测试 MCP 服务器 ===")
    tools = connect_to_sse()

    if tools is None:
        print("未能获取工具列表")
        return

    print("\n=== 开始测试各个工具 ===")

    # 测试获取订单
    print("\n=== 测试获取订单 ===")
    result = test_tool("get_orders", {"customer_name": "测试", "limit": 5})
    if result:
        print("订单查询结果：")
        print(json.dumps(result, ensure_ascii=False, indent=2))

    # 测试获取产品
    print("\n=== 测试获取产品列表 ===")
    result = test_tool("get_products", {"limit": 5})
    if result:
        print("产品查询结果：")
        print(json.dumps(result, ensure_ascii=False, indent=2))

    # 测试获取订单分析
    print("\n=== 测试获取订单分析 ===")
    date_from = (datetime.now() - timedelta(days=30)).strftime("%Y-%m-%d")
    date_to = datetime.now().strftime("%Y-%m-%d")

    result = test_tool(
        "get_order_analytics",
        {
            "analytics_type": "daily",
            "date_from": date_from,
            "date_to": date_to,
            "limit": 10,
        },
    )
    if result:
        print("订单分析结果：")
        print(json.dumps(result, ensure_ascii=False, indent=2))

    # 测试获取客户统计
    print("\n=== 测试获取客户统计 ===")
    result = test_tool("get_customer_stats", {"limit": 5})
    if result:
        print("客户统计结果：")
        print(json.dumps(result, ensure_ascii=False, indent=2))

    # 测试发送Excel邮件
    print("\n=== 测试发送Excel邮件 ===")
    result = test_tool(
        "send_excel_email",
        {
            "type": "orders",
            "email": "test@example.com",
            "subject": "测试订单导出",
            "message": "这是一封测试邮件",
            "filters": {"status": "all", "date_from": date_from, "date_to": date_to},
            "limit": 100,
        },
    )
    if result:
        print("邮件发送结果：")
        print(json.dumps(result, ensure_ascii=False, indent=2))

    print("\n=== 测试完成 ===")


if __name__ == "__main__":
    try:
        test_mcp_server()
    except Exception as e:
        print(f"测试过程中发生错误: {str(e)}")
    finally:
        print("\n测试完成")
