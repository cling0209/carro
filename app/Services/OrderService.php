<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected CartService $cartService,
        protected ShippingService $shippingService,
    ) {}

    public function createFromCart(Cart $cart, array $shipping, ?User $user = null): Order
    {
        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            throw new \InvalidArgumentException('El carrito está vacío.');
        }

        return DB::transaction(function () use ($cart, $shipping, $user) {
            $subtotal = 0;

            foreach ($cart->items as $item) {
                $product = $item->product;
                if (! $product || ! $product->is_active) {
                    throw new \InvalidArgumentException('Producto no disponible.');
                }
                if ($product->stock < $item->quantity) {
                    throw new \InvalidArgumentException("Stock insuficiente para {$product->name}.");
                }
                $subtotal += $item->unit_price * $item->quantity;
            }

            $quote = $this->shippingService->quote($cart, $shipping['region'] ?? '');
            $shippingAmount = $quote['amount'];
            $total = round($subtotal + $shippingAmount, 2);

            $order = Order::create([
                'user_id' => $user?->id,
                'status' => 'pending_payment',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_amount' => $shippingAmount,
                'shipping_zone' => $quote['zone'],
                'shipping_total_weight_kg' => $quote['total_weight_kg'],
                'shipping_rate_type' => $quote['rate_type'],
                'shipping_rate_label' => $quote['rate_label'],
                'shipping_weight_rate_id' => $quote['weight_rate_id'],
                'shipping_metadata' => $quote['metadata'],
                'total' => $total,
                'shipping_recipient_name' => $shipping['recipient_name'],
                'shipping_phone' => $shipping['phone'],
                'shipping_region' => $shipping['region'],
                'shipping_comuna' => $shipping['comuna'],
                'shipping_street' => $shipping['street'],
                'shipping_street_number' => $shipping['street_number'] ?? null,
                'shipping_apartment' => $shipping['apartment'] ?? null,
                'customer_email' => $shipping['email'],
                'customer_name' => $shipping['customer_name'] ?? $shipping['recipient_name'],
            ]);

            foreach ($cart->items as $item) {
                $lineTotal = round($item->unit_price * $item->quantity, 2);
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $lineTotal,
                ]);

                $item->product->decrement('stock', $item->quantity);
            }

            $order->recordStatus('pending_payment', null, 'Orden creada');
            $cart->items()->delete();

            return $order->load('items');
        });
    }
}
