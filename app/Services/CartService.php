<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartService
{
    public function resolve(Request $request): Cart
    {
        $sessionId = $request->header('X-Cart-Session') ?? $request->cookie('cart_session');

        if ($request->user()) {
            $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

            if ($sessionId) {
                $guestCart = Cart::where('session_id', $sessionId)->whereNull('user_id')->first();
                if ($guestCart) {
                    $this->mergeCarts($guestCart, $cart);
                    $guestCart->delete();
                }
            }

            return $cart->load('items.product.images');
        }

        if (! $sessionId) {
            $sessionId = (string) Str::uuid();
        }

        $cart = Cart::firstOrCreate(['session_id' => $sessionId], ['session_id' => $sessionId]);

        $cart->setAttribute('session_token', $sessionId);

        return $cart->load('items.product.images');
    }

    protected function mergeCarts(Cart $from, Cart $to): void
    {
        foreach ($from->items as $item) {
            $existing = $to->items()->where('product_id', $item->product_id)->first();
            if ($existing) {
                $existing->update(['quantity' => $existing->quantity + $item->quantity]);
            } else {
                $to->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ]);
            }
        }
    }

    public function addItem(Cart $cart, Product $product, int $quantity): CartItem
    {
        if ($product->stock < $quantity) {
            throw new \InvalidArgumentException('Stock insuficiente.');
        }

        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            $newQty = $item->quantity + $quantity;
            if ($product->stock < $newQty) {
                throw new \InvalidArgumentException('Stock insuficiente.');
            }
            $item->update(['quantity' => $newQty, 'unit_price' => $product->price]);

            return $item->fresh();
        }

        return $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $product->price,
        ]);
    }

    public function formatCart(Cart $cart): array
    {
        $cart->loadMissing('items.product.images');
        $totals = $cart->recalculateTotals();

        return [
            'id' => $cart->id,
            'session_id' => $cart->session_id ?? $cart->getAttribute('session_token'),
            'items' => $cart->items->map(fn (CartItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => round($item->unit_price * $item->quantity, 2),
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'stock' => $item->product->stock,
                    'image' => $item->product->resolveImageUrl(),
                ] : null,
            ]),
            'subtotal' => $totals['subtotal'],
            'item_count' => $totals['item_count'],
        ];
    }
}
