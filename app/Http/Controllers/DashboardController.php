<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Build orders query with search filters
        $ordersQuery = Order::with('product');

        // Search by customer name
        if ($request->filled('customer_name')) {
            $ordersQuery->where('name', 'like', '%' . $request->customer_name . '%');
        }

        // Search by status
        if ($request->filled('status')) {
            $ordersQuery->where('status', $request->status);
        }

        // Search by product name
        if ($request->filled('product_name')) {
            $ordersQuery->whereHas('product', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->product_name . '%');
            });
        }

        // Search by date range
        if ($request->filled('date_from')) {
            $ordersQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $ordersQuery->whereDate('created_at', '<=', $request->date_to);
        }

        // Get orders with pagination (preserve search parameters)
        $orders = $ordersQuery->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get all products for dropdown
        $products = Product::all();

        // Get distinct statuses for dropdown
        $statuses = Order::distinct()->pluck('status')->filter();

        return view('dashboard', compact('orders', 'products', 'statuses'));
    }
}
