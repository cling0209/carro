<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ShippingSetting;
use App\Models\ShippingWeightRate;
use Illuminate\Support\Str;

class ShippingService
{
    public const ZONE_RM = 'rm';

    public const ZONE_REGIONS = 'regions';

    public const RATE_FLAT_RM = 'flat_rm';

    public const RATE_WEIGHT_BAND = 'weight_band';

    public function quote(Cart $cart, string $region): array
    {
        $cart->loadMissing('items.product');

        if ($cart->items->isEmpty()) {
            throw new \InvalidArgumentException('El carrito está vacío.');
        }

        $weightBreakdown = $this->buildWeightBreakdown($cart);
        $totalWeight = $weightBreakdown['total_weight_kg'];

        if ($this->isMetropolitanRegion($region)) {
            $amount = ShippingSetting::getFloat('rm_flat_rate', 3990);

            return [
                'amount' => round($amount, 2),
                'zone' => self::ZONE_RM,
                'total_weight_kg' => $totalWeight,
                'rate_type' => self::RATE_FLAT_RM,
                'rate_label' => 'Tarifa fija Región Metropolitana',
                'weight_rate_id' => null,
                'metadata' => [
                    'region' => $region,
                    'rm_flat_rate' => $amount,
                    'items' => $weightBreakdown['items'],
                ],
            ];
        }

        $rate = $this->findWeightRate($totalWeight);

        if (! $rate) {
            throw new \InvalidArgumentException(
                'No hay tarifa de envío configurada para el peso total de '.number_format($totalWeight, 2, ',', '.').' kg.'
            );
        }

        return [
            'amount' => round((float) $rate->price, 2),
            'zone' => self::ZONE_REGIONS,
            'total_weight_kg' => $totalWeight,
            'rate_type' => self::RATE_WEIGHT_BAND,
            'rate_label' => $rate->label,
            'weight_rate_id' => $rate->id,
            'metadata' => [
                'region' => $region,
                'weight_rate' => [
                    'id' => $rate->id,
                    'label' => $rate->label,
                    'min_weight_kg' => $rate->min_weight_kg,
                    'max_weight_kg' => $rate->max_weight_kg,
                    'price' => $rate->price,
                ],
                'items' => $weightBreakdown['items'],
            ],
        ];
    }

    public function isMetropolitanRegion(string $region): bool
    {
        $normalized = Str::lower(trim($region));

        return Str::contains($normalized, 'metropolitana');
    }

    protected function buildWeightBreakdown(Cart $cart): array
    {
        $defaultWeight = ShippingSetting::getFloat('default_product_weight_kg', 1.0);
        $items = [];
        $total = 0.0;

        foreach ($cart->items as $item) {
            /** @var CartItem $item */
            $product = $item->product;
            $unitWeight = $this->productWeightKg($product, $defaultWeight);
            $lineWeight = round($unitWeight * $item->quantity, 3);

            $items[] = [
                'product_id' => $product?->id,
                'sku' => $product?->sku,
                'name' => $product?->name,
                'quantity' => $item->quantity,
                'unit_weight_kg' => $unitWeight,
                'line_weight_kg' => $lineWeight,
                'used_default_weight' => $product?->weight_kg === null,
            ];

            $total += $lineWeight;
        }

        return [
            'total_weight_kg' => round($total, 3),
            'items' => $items,
        ];
    }

    protected function productWeightKg(?Product $product, float $default): float
    {
        if (! $product || $product->weight_kg === null) {
            return $default;
        }

        return max(0, (float) $product->weight_kg);
    }

    protected function findWeightRate(float $totalWeightKg): ?ShippingWeightRate
    {
        return ShippingWeightRate::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('min_weight_kg')
            ->get()
            ->first(fn (ShippingWeightRate $rate) => $rate->matchesWeight($totalWeightKg));
    }
}
