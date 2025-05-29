<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCP Service 測試頁面</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-center mb-8">MCP Service 測試頁面</h1>
        
        <!-- MCP Service Info -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">MCP Service 資訊</h2>
            <div id="mcp-info" class="bg-gray-50 p-4 rounded">
                <button id="load-info" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    載入 MCP Service 資訊
                </button>
            </div>
        </div>

        <!-- Available Tools -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">可用工具</h2>
            <div id="tools-list" class="bg-gray-50 p-4 rounded">
                <button id="load-tools" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    載入可用工具
                </button>
            </div>
        </div>

        <!-- Tool Testing -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">工具測試</h2>
            
            <!-- Quick Tests -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                <button onclick="testGetOrders()" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                    測試獲取訂單
                </button>
                <button onclick="testGetProducts()" class="bg-indigo-500 text-white px-4 py-2 rounded hover:bg-indigo-600">
                    測試獲取產品
                </button>
                <button onclick="testCustomerStats()" class="bg-pink-500 text-white px-4 py-2 rounded hover:bg-pink-600">
                    測試客戶統計
                </button>
                <button onclick="testOrderAnalytics()" class="bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">
                    測試訂單分析
                </button>
                <button onclick="testOrdersByStatus()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    測試狀態篩選
                </button>
                <button onclick="testOrdersByProduct()" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                    測試產品篩選
                </button>
            </div>

            <!-- Custom Tool Call -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold mb-4">自定義工具調用</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">工具名稱</label>
                        <select id="tool-name" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">選擇工具...</option>
                            <option value="get_orders">get_orders</option>
                            <option value="get_products">get_products</option>
                            <option value="get_customer_stats">get_customer_stats</option>
                            <option value="get_order_analytics">get_order_analytics</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">參數 (JSON)</label>
                        <textarea id="tool-params" class="w-full border border-gray-300 rounded-md px-3 py-2" rows="3"
                                  placeholder='{"limit": 5, "status": "completed"}'></textarea>
                    </div>
                </div>
                <button onclick="callCustomTool()" class="mt-4 bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">
                    執行自定義調用
                </button>
            </div>

            <!-- Results -->
            <div class="mt-6">
                <h3 class="text-lg font-semibold mb-4">測試結果</h3>
                <div id="test-results" class="bg-gray-900 text-green-400 p-4 rounded font-mono text-sm overflow-auto max-h-96">
                    測試結果將顯示在這裡...
                </div>
            </div>
        </div>
    </div>

    <script>
        function logResult(title, data) {
            const timestamp = new Date().toLocaleTimeString();
            const resultDiv = document.getElementById('test-results');
            resultDiv.innerHTML += `\n[${timestamp}] ${title}:\n${JSON.stringify(data, null, 2)}\n${'='.repeat(50)}\n`;
            resultDiv.scrollTop = resultDiv.scrollHeight;
        }

        function clearResults() {
            document.getElementById('test-results').innerHTML = '測試結果將顯示在這裡...';
        }

        $('#load-info').click(function() {
            $.get('/mcp/info')
                .done(function(data) {
                    $('#mcp-info').html(`
                        <h3 class="font-semibold">服務名稱: ${data.name}</h3>
                        <p>版本: ${data.version}</p>
                        <p>描述: ${data.description}</p>
                        <p>可用工具數量: ${data.tools.length}</p>
                        <div class="mt-2">
                            <strong>API 端點:</strong>
                            <ul class="ml-4 mt-1">
                                ${Object.entries(data.endpoints).map(([key, url]) => 
                                    `<li><code class="bg-gray-200 px-1 rounded">${key}</code>: ${url}</li>`
                                ).join('')}
                            </ul>
                        </div>
                    `);
                    logResult('MCP Service Info', data);
                })
                .fail(function(xhr) {
                    logResult('Error loading MCP info', {error: xhr.responseText});
                });
        });

        $('#load-tools').click(function() {
            $.get('/mcp/tools')
                .done(function(data) {
                    $('#tools-list').html(`
                        <div class="space-y-2">
                            ${data.tools.map(tool => `
                                <div class="border border-gray-200 rounded p-3">
                                    <h4 class="font-semibold">${tool.name}</h4>
                                    <p class="text-sm text-gray-600">${tool.description}</p>
                                    <details class="mt-2">
                                        <summary class="cursor-pointer text-blue-600">查看參數</summary>
                                        <pre class="mt-2 bg-gray-100 p-2 rounded text-xs">${JSON.stringify(tool.inputSchema, null, 2)}</pre>
                                    </details>
                                </div>
                            `).join('')}
                        </div>
                    `);
                    logResult('Available Tools', data);
                })
                .fail(function(xhr) {
                    logResult('Error loading tools', {error: xhr.responseText});
                });
        });

        function callTool(name, params = {}) {
            $.ajax({
                url: '/mcp/tools/call',
                method: 'POST',
                data: {
                    name: name,
                    arguments: params
                },
                success: function(data) {
                    logResult(`Tool Call: ${name}`, data);
                },
                error: function(xhr) {
                    logResult(`Error calling ${name}`, {error: xhr.responseText});
                }
            });
        }

        function testGetOrders() {
            callTool('get_orders', {limit: 5});
        }

        function testGetProducts() {
            callTool('get_products', {limit: 5});
        }

        function testCustomerStats() {
            callTool('get_customer_stats', {});
        }

        function testOrderAnalytics() {
            callTool('get_order_analytics', {group_by: 'status'});
        }

        function testOrdersByStatus() {
            callTool('get_orders', {status: 'completed', limit: 3});
        }

        function testOrdersByProduct() {
            callTool('get_orders', {product_name: '可口可樂', limit: 3});
        }

        function callCustomTool() {
            const toolName = document.getElementById('tool-name').value;
            const paramsText = document.getElementById('tool-params').value;
            
            if (!toolName) {
                alert('請選擇工具名稱');
                return;
            }

            let params = {};
            if (paramsText.trim()) {
                try {
                    params = JSON.parse(paramsText);
                } catch (e) {
                    alert('參數格式錯誤，請使用有效的 JSON 格式');
                    return;
                }
            }

            callTool(toolName, params);
        }

        // Clear results button
        $(document).ready(function() {
            $('#test-results').before('<button onclick="clearResults()" class="mb-2 bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">清除結果</button>');
        });
    </script>
</body>
</html>
