<?php

/**
 * Windows兼容的SSE强制关闭连接测试
 */

echo "=== SSE Windows测试 ===\n\n";

$baseUrl = 'http://localhost:8080';

// 测试1: 基本连接测试
echo "1. 测试基本SSE连接...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/mcp/sse');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/event-stream',
    'Cache-Control: no-cache'
]);

// 设置写入函数来处理SSE数据
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
    echo "收到数据: " . trim($data) . "\n";
    
    // 如果收到ping消息，表示连接正常
    if (strpos($data, 'event: ping') !== false || strpos($data, 'event: welcome') !== false) {
        echo "✓ SSE连接正常工作\n";
        return 0; // 返回0停止curl
    }
    
    return strlen($data);
});

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    echo "✓ HTTP状态码: $httpCode (正常)\n";
} else {
    echo "✗ HTTP状态码: $httpCode\n";
}

if ($error) {
    echo "cURL错误: $error\n";
}

echo "\n";

// 测试2: 发送MCP请求
echo "2. 测试MCP JSON-RPC请求...\n";

$mcpRequest = json_encode([
    'jsonrpc' => '2.0',
    'method' => 'tools/list',
    'params' => [],
    'id' => 'test-request'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/mcp/sse');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $mcpRequest);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: text/event-stream',
    'Cache-Control: no-cache'
]);

$responseCount = 0;
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) use (&$responseCount) {
    $responseCount++;
    echo "响应 $responseCount: " . trim($data) . "\n";
    
    // 如果收到工具列表响应，表示MCP正常工作
    if (strpos($data, 'tools') !== false || strpos($data, 'result') !== false) {
        echo "✓ MCP工具列表请求成功\n";
        return 0; // 停止接收
    }
    
    // 限制响应数量
    if ($responseCount > 5) {
        return 0;
    }
    
    return strlen($data);
});

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP状态码: $httpCode\n";
echo "\n";

// 测试3: 快速连接断开测试
echo "3. 测试快速连接断开...\n";

for ($i = 1; $i <= 3; $i++) {
    echo "连接 $i: ";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/mcp/sse');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); // 2秒后自动断开
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/event-stream'
    ]);
    
    $start = microtime(true);
    $result = curl_exec($ch);
    $duration = microtime(true) - $start;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "持续 " . round($duration, 2) . "s, HTTP: $httpCode ";
    
    if ($httpCode === 200 && $duration >= 1.8) {
        echo "✓\n";
    } else {
        echo "✗\n";
    }
    
    sleep(1); // 等待1秒
}

echo "\n";

// 测试4: 检查服务器日志
echo "4. 建议检查Laravel日志:\n";
echo "   tail -f storage/logs/laravel.log\n";
echo "   查看连接建立和断开的日志记录\n\n";

echo "=== 测试总结 ===\n";
echo "✓ 基本SSE功能测试完成\n";
echo "✓ MCP JSON-RPC请求测试完成\n";
echo "✓ 连接断开测试完成\n";
echo "\n";
echo "如果所有测试都通过，SSE强制关闭功能应该正常工作。\n";
echo "请在Ubuntu服务器上查看Laravel日志以确认连接管理功能。\n";
