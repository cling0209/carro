<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingWeightRate extends Model
{
    protected $fillable = [
        'label',
        'min_weight_kg',
        'max_weight_kg',
        'price',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'min_weight_kg' => 'decimal:3',
            'max_weight_kg' => 'decimal:3',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shipping_weight_rate_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function matchesWeight(float $weightKg): bool
    {
        if ($weightKg < (float) $this->min_weight_kg) {
            return false;
        }

        if ($this->max_weight_kg !== null && $weightKg >= (float) $this->max_weight_kg) {
            return false;
        }

        return true;
    }
}
