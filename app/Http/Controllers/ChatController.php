<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $userMessage = $request->input('message');
        $n8nWebhookUrl = env('N8N_WEBHOOK_URL');

        if (!$n8nWebhookUrl) {
            return response()->json([
                'success' => false,
                'error' => 'n8n webhook URL 未配置',
            ], 500);
        }

        try {
            // 發送消息到 n8n webhook
            $response = Http::timeout(30)->post($n8nWebhookUrl, [
                'message' => $userMessage,
                'timestamp' => now()->toISOString(),
                'source' => 'mcp_demo_chat',
                'user_id' => $request->session()->getId(),
                'mcp_service_url' => url('/mcp'),
                'mcp_sse_endpoint' => url('/mcp/sse'),
                'mcp_stdio_endpoint' => url('/mcp/stdio'),
                'mcp_config' => [
                    'protocol_version' => '2024-11-05',
                    'transport' => 'sse',
                    'command' => 'php artisan mcp:server',
                    'cwd' => base_path()
                ],
                'context' => [
                    'app_name' => config('app.name'),
                    'app_url' => config('app.url'),
                    'available_tools' => [
                        'get_orders',
                        'get_products', 
                        'get_customer_stats',
                        'get_order_analytics'
                    ]
                ]
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // 檢查 n8n 回應格式
                if (isset($responseData['response'])) {
                    return response()->json([
                        'success' => true,
                        'response' => $responseData['response'],
                        'source' => 'n8n_webhook'
                    ]);
                } elseif (isset($responseData['message'])) {
                    return response()->json([
                        'success' => true,
                        'response' => $responseData['message'],
                        'source' => 'n8n_webhook'
                    ]);
                } else {
                    // 如果 n8n 直接返回字符串
                    return response()->json([
                        'success' => true,
                        'response' => is_string($responseData) ? $responseData : json_encode($responseData),
                        'source' => 'n8n_webhook'
                    ]);
                }
            } else {
                Log::error('n8n webhook 調用失敗', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $n8nWebhookUrl
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'AI 服務暫時不可用 (錯誤代碼: ' . $response->status() . ')',
                ], 500);
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('n8n webhook 連接超時', [
                'error' => $e->getMessage(),
                'url' => $n8nWebhookUrl
            ]);

            return response()->json([
                'success' => false,
                'error' => 'AI 服務連接超時，請稍後再試',
            ], 500);

        } catch (\Exception $e) {
            Log::error('n8n webhook 調用異常', [
                'error' => $e->getMessage(),
                'url' => $n8nWebhookUrl
            ]);

            return response()->json([
                'success' => false,
                'error' => 'AI 服務暫時不可用，請稍後再試',
            ], 500);
        }
    }
}
