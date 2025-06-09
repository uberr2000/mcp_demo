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
            <div class="lg:col-span-2">                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-4">訂單列表</h2>
                    
                    <!-- Search Form -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">客戶姓名</label>
                                <input type="text" 
                                       id="customer_name" 
                                       name="customer_name" 
                                       value="{{ request('customer_name') }}"
                                       placeholder="搜尋客戶姓名..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">訂單狀態</label>
                                <select id="status" 
                                        name="status" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">所有狀態</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label for="product_name" class="block text-sm font-medium text-gray-700 mb-1">產品名稱</label>
                                <input type="text" 
                                       id="product_name" 
                                       name="product_name" 
                                       value="{{ request('product_name') }}"
                                       placeholder="搜尋產品名稱..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">開始日期</label>
                                <input type="date" 
                                       id="date_from" 
                                       name="date_from" 
                                       value="{{ request('date_from') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">結束日期</label>
                                <input type="date" 
                                       id="date_to" 
                                       name="date_to" 
                                       value="{{ request('date_to') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            
                            <div class="flex items-end gap-2">
                                <button type="submit" 
                                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    搜尋
                                </button>
                                <a href="{{ route('dashboard') }}" 
                                   class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    重置
                                </a>
                            </div>
                        </form>
                        
                        <!-- Search Results Summary -->
                        @if(request()->hasAny(['customer_name', 'status', 'product_name', 'date_from', 'date_to']))
                            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-blue-800 text-sm font-medium">
                                        搜尋結果：共找到 {{ $orders->total() }} 筆符合條件的訂單
                                    </span>
                                </div>
                                <div class="mt-2 text-xs text-blue-600">
                                    @if(request('customer_name'))
                                        <span class="inline-block bg-blue-100 px-2 py-1 rounded mr-2">客戶：{{ request('customer_name') }}</span>
                                    @endif
                                    @if(request('status'))
                                        <span class="inline-block bg-blue-100 px-2 py-1 rounded mr-2">狀態：{{ request('status') }}</span>
                                    @endif
                                    @if(request('product_name'))
                                        <span class="inline-block bg-blue-100 px-2 py-1 rounded mr-2">產品：{{ request('product_name') }}</span>
                                    @endif
                                    @if(request('date_from'))
                                        <span class="inline-block bg-blue-100 px-2 py-1 rounded mr-2">開始：{{ request('date_from') }}</span>
                                    @endif
                                    @if(request('date_to'))
                                        <span class="inline-block bg-blue-100 px-2 py-1 rounded mr-2">結束：{{ request('date_to') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    
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
                            webhookUrl: '{{ $n8nWebhookUrl ?? '' }}'
                        });
                    </script>            
                </div>
            </div>
        </div>
    </div><script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Auto-submit search form on status change
            $('#status').on('change', function() {
                $(this).closest('form').submit();
            });

            // Auto-submit search form on date change
            $('#date_from, #date_to').on('change', function() {
                $(this).closest('form').submit();
            });

            // Add debounced search for text inputs
            let searchTimeout;
            $('#customer_name, #product_name').on('input', function() {
                clearTimeout(searchTimeout);
                const form = $(this).closest('form');
                
                searchTimeout = setTimeout(function() {
                    form.submit();
                }, 800); // Wait 800ms after user stops typing
            });

            // Clear search button functionality
            $('.clear-search').on('click', function(e) {
                e.preventDefault();
                $('#customer_name, #product_name, #date_from, #date_to').val('');
                $('#status').val('');
                $(this).closest('form').submit();
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
                    },
                    success: function(response) {
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
