<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MCP Demo - 訂單管理系統</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">MCP Demo - 訂單管理系統</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Orders Section -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-4">訂單列表</h2>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-auto">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">交易ID</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">客戶姓名</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">產品</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">數量</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金額</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">建立時間</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($orders as $order)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">{{ $order->transaction_id }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">{{ $order->name }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">{{ $order->product->name }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">{{ $order->quantity }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">HK${{ number_format($order->amount, 2) }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if($order->status == 'completed') bg-green-100 text-green-800
                                            @elseif($order->status == 'pending') bg-yellow-100 text-yellow-800
                                            @elseif($order->status == 'processing') bg-blue-100 text-blue-800
                                            @elseif($order->status == 'cancelled') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">{{ $order->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $orders->links() }}
                    </div>
                </div>
                
                <!-- Products Section -->
                <div class="bg-white rounded-lg shadow-md p-6 mt-8">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-4">產品列表</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($products as $product)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <h3 class="font-semibold text-lg text-gray-800">{{ $product->name }}</h3>
                            <p class="text-gray-600 text-sm mt-1">{{ $product->description }}</p>
                            <p class="text-blue-600 font-bold mt-2">HK${{ number_format($product->price, 2) }}</p>
                            <p class="text-gray-500 text-sm">庫存: {{ $product->stock_quantity }} | 類別: {{ $product->category }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- Chat Section -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 h-full">                    <h2 class="text-2xl font-semibold text-gray-700 mb-4">n8n AI 助手</h2>
                    
                    <div id="chat-messages" class="h-64 overflow-y-auto border border-gray-200 rounded-lg p-4 mb-4 bg-gray-50">
                        <div class="text-gray-500 text-sm">
                            <div class="mb-2 text-green-600 font-medium">🤖 已連接到 n8n AI 工作流程</div>
                            您可以詢問關於訂單和產品的問題，AI 會通過 MCP 服務查詢資料：<br>
                            <div class="mt-2 space-y-1">
                                <div>• "顯示最近 5 筆已完成的訂單"</div>
                                <div>• "TXN000001 的訂單詳情是什麼？"</div>
                                <div>• "陳大明有多少筆訂單？"</div>
                                <div>• "分析一下訂單狀態分布"</div>
                                <div>• "有什麼飲料產品？"</div>
                                <div>• "最受歡迎的產品是什麼？"</div>
                            </div>
                            <div class="mt-3 text-xs text-blue-600">
                                ⚡ 通過 n8n webhook 整合，支援 MCP 工具調用
                            </div>
                        </div>
                    </div>
                    
                    <form id="chat-form" class="flex">
                        <input type="text" id="chat-input" placeholder="輸入您的問題..." 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            發送
                        </button>
                    </form>
                    
                    <div id="loading" class="hidden mt-2 text-center">
                        <div class="inline-flex items-center">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                            <span class="ml-2 text-sm text-gray-600">AI 正在思考...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('#chat-form').on('submit', function(e) {
                e.preventDefault();
                
                const message = $('#chat-input').val().trim();
                if (!message) return;
                
                // Add user message to chat
                addMessageToChat('您', message, 'user');
                $('#chat-input').val('');
                $('#loading').removeClass('hidden');
                
                // Send message to backend
                $.ajax({
                    url: '{{ route("chat") }}',
                    method: 'POST',
                    data: {
                        message: message
                    },                    success: function(response) {
                        $('#loading').addClass('hidden');
                        if (response.success) {
                            const senderName = response.source === 'n8n_webhook' ? 'n8n AI' : 'AI 助手';
                            addMessageToChat(senderName, response.response, 'ai');
                        } else {
                            addMessageToChat('系統', response.error || '發生錯誤，請稍後再試', 'error');
                        }
                    },
                    error: function() {
                        $('#loading').addClass('hidden');
                        addMessageToChat('系統', '網絡錯誤，請稍後再試', 'error');
                    }
                });
            });

            function addMessageToChat(sender, message, type) {
                const chatMessages = $('#chat-messages');
                const messageClass = type === 'user' ? 'text-blue-600' : 
                                   type === 'error' ? 'text-red-600' : 'text-green-600';
                
                const messageHtml = `
                    <div class="mb-3">
                        <div class="font-semibold text-sm ${messageClass}">${sender}:</div>
                        <div class="text-gray-800 text-sm mt-1 whitespace-pre-wrap">${message}</div>
                    </div>
                `;
                
                chatMessages.append(messageHtml);
                chatMessages.scrollTop(chatMessages[0].scrollHeight);
            }
        });
    </script>
</body>
</html>
