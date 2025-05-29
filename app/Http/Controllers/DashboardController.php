<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get orders with pagination
        $orders = Order::with('product')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Get all products
        $products = Product::all();

        return view('dashboard', compact('orders', 'products'));
    }
}
