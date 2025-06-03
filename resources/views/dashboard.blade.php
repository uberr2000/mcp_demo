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
                <div class="bg-white rounded-lg shadow-md p-6 h-full">        
                    <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet" />
                    <script type="module">
                        import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

                        createChat({
                            webhookUrl: 'https://autoflow.ink.net.tw/webhook/96c0bbee-192e-4913-b23a-5fc7c805b3a3/chat'
                        });
                    </script>            
                    
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
