<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ProductsExport implements FromArray, WithHeadings, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    protected $products;

    public function __construct(array $products)
    {
        $this->products = $products;
    }    public function array(): array
    {
        return array_map(function ($product) {
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'description' => $product['description'] ?? '',
                'category' => $product['category'] ?? 'N/A',
                'price' => $product['price'],
                'stock_quantity' => $product['stock_quantity'] ?? 0,
                'created_date' => $product['created_at'] ? date('Y-m-d H:i:s', strtotime($product['created_at'])) : 'N/A',
                'updated_date' => $product['updated_at'] ? date('Y-m-d H:i:s', strtotime($product['updated_at'])) : 'N/A',
            ];
        }, $this->products);
    }    public function headings(): array
    {
        return [
            '產品ID',
            '產品名稱',
            '產品描述',
            '產品類別',
            '價格',
            '庫存數量',
            '創建日期',
            '更新日期',
        ];
    }    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true, 'size' => 12]],
            
            // Set background color for header
            'A1:H1' => [
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
            'E' => NumberFormat::FORMAT_CURRENCY_USD, // Price
            'F' => NumberFormat::FORMAT_NUMBER, // Stock quantity
            'G' => NumberFormat::FORMAT_DATE_DATETIME, // Created date
            'H' => NumberFormat::FORMAT_DATE_DATETIME, // Updated date
        ];
    }
}
