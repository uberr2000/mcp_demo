<?php

namespace App\MCP\Tools;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Order;
use App\Models\Product;
use App\Exports\OrdersExport;
use App\Exports\ProductsExport;
use OPGG\LaravelMcpServer\Enums\ProcessMessageType;
use OPGG\LaravelMcpServer\Exceptions\Enums\JsonRpcErrorCode;
use OPGG\LaravelMcpServer\Exceptions\JsonRpcErrorException;
use OPGG\LaravelMcpServer\Services\ToolService\ToolInterface;

class SendExcelEmailTool implements ToolInterface
{
    public function messageType(): ProcessMessageType
    {
        return ProcessMessageType::HTTP;
    }
    public function name(): string
    {
        return 'send_excel_email';
    }

    public function description(): string
    {
        return '生成並通過 Amazon SES 發送訂單或產品的 Excel 文件到指定郵箱';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => ['orders', 'products'],
                    'description' => '要導出的數據類型：orders(訂單) 或 products(產品)'
                ],
                'email' => [
                    'type' => 'string',
                    'format' => 'email',
                    'description' => '接收Excel文件的郵箱地址'
                ],
                'subject' => [
                    'type' => 'string',
                    'description' => '郵件主題 - Optional field (default: 系統自動生成)'
                ],
                'message' => [
                    'type' => 'string',
                    'description' => '郵件內容 - Optional field (default: 系統自動生成)'
                ],
                'filters' => [
                    'type' => 'object',
                    'description' => '篩選條件 - Optional field',
                    'properties' => [                        'status' => [
                            'type' => 'string',
                            'enum' => ['pending', 'processing', 'completed', 'cancelled', 'refunded', 'all'],
                            'description' => '訂單狀態篩選（僅適用於訂單導出）- Use "all" to include all statuses'
                        ],
                        'customer_name' => [
                            'type' => 'string',
                            'description' => '客戶姓名篩選（僅適用於訂單導出）'
                        ],
                        'product_name' => [
                            'type' => 'string',
                            'description' => '產品名稱篩選'
                        ],
                        'date_from' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => '開始日期 (YYYY-MM-DD format)'
                        ],
                        'date_to' => [
                            'type' => 'string',
                            'format' => 'date',
                            'description' => '結束日期 (YYYY-MM-DD format)'
                        ],
                        'category' => [
                            'type' => 'string',
                            'description' => '產品類別篩選（僅適用於產品導出）'
                        ],
                        'active' => [
                            'type' => 'boolean',
                            'description' => '是否啟用篩選（僅適用於產品導出）'
                        ]
                    ]
                ],
                'limit' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 10000,
                    'description' => '導出記錄數量限制 - Optional field (default: 1000, max: 10000)'
                ]
            ],
            'required' => ['type', 'email']        ];
    }

    public function annotations(): array
    {
        return [];
    }

    public function execute(array $arguments): array
    {
        try {
            $type = $arguments['type'];
            $email = $arguments['email'];
            $subject = $arguments['subject'] ?? null;
            $message = $arguments['message'] ?? null;
            $filters = $arguments['filters'] ?? [];
            $limit = $arguments['limit'] ?? 1000;

            Log::info('SendExcelEmailTool execution started:', [
                'type' => $type,
                'email' => $email,
                'filters' => $filters,
                'limit' => $limit
            ]);            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new JsonRpcErrorException(
                    '無效的郵箱地址格式',
                    JsonRpcErrorCode::INVALID_PARAMS
                );
            }            // Generate filename with timestamp
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "{$type}_export_{$timestamp}.xlsx";
            $filePath = "exports/{$filename}";

            // Ensure the exports directory exists
            if (!Storage::exists('exports')) {
                Storage::makeDirectory('exports');
            }            // Generate Excel file based on type
            if ($type === 'orders') {
                $data = $this->getOrdersData($filters, $limit);
                
                // Log data for debugging
                Log::info('Orders data retrieved:', ['count' => count($data), 'sample' => array_slice($data, 0, 2)]);
                
                Excel::store(new OrdersExport($data), $filePath, 'local');
                
                $defaultSubject = "訂單數據導出 - {$timestamp}";
                $defaultMessage = "附件包含您請求的訂單數據導出文件。\n\n導出時間：{$timestamp}\n記錄數量：" . count($data);
            } else {
                $data = $this->getProductsData($filters, $limit);
                
                // Log data for debugging
                Log::info('Products data retrieved:', ['count' => count($data), 'sample' => array_slice($data, 0, 2)]);
                
                Excel::store(new ProductsExport($data), $filePath, 'local');
                
                $defaultSubject = "產品數據導出 - {$timestamp}";
                $defaultMessage = "附件包含您請求的產品數據導出文件。\n\n導出時間：{$timestamp}\n記錄數量：" . count($data);
            }

            // Verify the Excel file was created
            if (!Storage::exists($filePath)) {
                throw new \Exception("Excel file was not created at path: {$filePath}");
            }

            // Log file creation success
            $fileSize = Storage::size($filePath);
            Log::info('Excel file created successfully:', [
                'path' => $filePath,
                'size' => $fileSize,
                'full_path' => storage_path("app/{$filePath}")
            ]);

            // Use default subject/message if not provided
            $emailSubject = $subject ?? $defaultSubject;
            $emailMessage = $message ?? $defaultMessage;            // Send email with Excel attachment via SES
            $fullPath = Storage::disk('local')->path($filePath);
              Mail::send([], [], function ($mail) use ($email, $emailSubject, $emailMessage, $fullPath, $filename) {
                $mail->to($email)
                     ->subject($emailSubject)
                     ->text($emailMessage)
                     ->attach($fullPath, [
                         'as' => $filename,
                         'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                     ]);
            });

            // Clean up the temporary file
            Storage::delete($filePath);

            Log::info('SendExcelEmailTool execution completed successfully:', [
                'type' => $type,
                'email' => $email,
                'records_count' => count($data),
                'filename' => $filename
            ]);

            return [
                'success' => true,
                'message' => "Excel 文件已成功發送到 {$email}",
                'data' => [
                    'type' => $type,
                    'email' => $email,
                    'filename' => $filename,
                    'records_count' => count($data),
                    'export_time' => $timestamp,
                    'subject' => $emailSubject
                ]
            ];

        } catch (JsonRpcErrorException $e) {
            throw $e;        } catch (\Exception $e) {
            Log::error('SendExcelEmailTool execution failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new JsonRpcErrorException(
                '發送郵件失敗：' . $e->getMessage(),
                JsonRpcErrorCode::INTERNAL_ERROR
            );
        }
    }

    private function getOrdersData(array $filters, int $limit): array
    {
        $query = Order::with('product');        // Apply filters
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'all') {
                // Don't apply any status filter when "all" is specified
                Log::info('Status filter set to "all" - no status filtering applied');
            } else {
                $query->where('status', $filters['status']);
                Log::info('Applied status filter: ' . $filters['status']);
            }
        }

        if (!empty($filters['customer_name'])) {
            $query->where('name', 'like', '%' . $filters['customer_name'] . '%');
        }

        if (!empty($filters['product_name'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['product_name'] . '%');
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get()
                    ->toArray();
    }

    private function getProductsData(array $filters, int $limit): array
    {
        $query = Product::query();

        // Apply filters
        if (!empty($filters['product_name'])) {
            $query->where('name', 'like', '%' . $filters['product_name'] . '%');
        }

        if (!empty($filters['category'])) {
            $query->where('category', 'like', '%' . $filters['category'] . '%');
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('name')
                    ->limit($limit)
                    ->get()
                    ->toArray();
    }
}
