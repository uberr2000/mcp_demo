<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OrdersExport implements FromArray, WithHeadings, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    protected $orders;

    public function __construct(array $orders)
    {
        $this->orders = $orders;
    }

    public function array(): array
    {
        return array_map(function ($order) {
            return [
                'transaction_id' => $order['transaction_id'],
                'customer_name' => $order['name'],
                'product_name' => $order['product']['name'] ?? 'N/A',
                'product_category' => $order['product']['category'] ?? 'N/A',
                'quantity' => $order['quantity'],
                'unit_price' => $order['product']['price'] ?? 0,
                'total_amount' => $order['amount'],
                'status' => $order['status'],
                'order_date' => $order['created_at'] ? date('Y-m-d H:i:s', strtotime($order['created_at'])) : 'N/A',
                'customer_email' => $order['email'] ?? 'N/A',
                'customer_phone' => $order['phone'] ?? 'N/A',
                'shipping_address' => $order['address'] ?? 'N/A',
                'notes' => $order['notes'] ?? '',
            ];
        }, $this->orders);
    }

    public function headings(): array
    {
        return [
            '交易ID',
            '客戶姓名',
            '產品名稱',
            '產品類別',
            '數量',
            '單價',
            '總金額',
            '訂單狀態',
            '訂單日期',
            '客戶郵箱',
            '客戶電話',
            '送貨地址',
            '備註',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true, 'size' => 12]],
            
            // Set background color for header
            'A1:M1' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFE2E8F0']
                ]
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_CURRENCY_USD, // Unit price
            'G' => NumberFormat::FORMAT_CURRENCY_USD, // Total amount
            'I' => NumberFormat::FORMAT_DATE_DATETIME, // Order date
        ];
    }
}
