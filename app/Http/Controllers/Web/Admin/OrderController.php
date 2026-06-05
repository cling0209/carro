<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->query('payment_status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->query('q');
                $q->where(function ($inner) use ($term) {
                    $inner->where('uuid', 'ilike', "%{$term}%")
                        ->orWhere('customer_email', 'ilike', "%{$term}%")
                        ->orWhere('customer_name', 'ilike', "%{$term}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load([
            'items',
            'paymentTransactions',
            'statusHistory',
            'user',
        ]);

        return view('admin.orders.show', compact('order'));
    }
}
