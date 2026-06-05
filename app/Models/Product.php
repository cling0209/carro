<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'sku', 'familia', 'name', 'slug', 'description',
        'price', 'compare_at_price', 'stock', 'weight_kg', 'is_active', 'is_featured', 'metadata',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'weight_kg' => 'decimal:3',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function getImageUrlAttribute(): string
    {
        return $this->resolveImageUrl();
    }

    public function resolveImageUrl(): string
    {
        $computed = $this->buildExternalImageUrl();

        if ($computed !== null) {
            return $computed;
        }

        $image = $this->relationLoaded('images')
            ? ($this->images->firstWhere('is_primary', true) ?? $this->images->first())
            : $this->images()->where('is_primary', true)->first()
                ?? $this->images()->orderBy('sort_order')->first();

        if ($image?->url) {
            return $image->url;
        }

        return '';
    }

    public function buildExternalImageUrl(): ?string
    {
        $base = rtrim((string) config('products.image_base_url'), '/');

        if ($base === '' || ! $this->familia || ! $this->sku) {
            return null;
        }

        $pattern = (string) config('products.image_filename_pattern', '{codigo}_medium.jpg');
        $filename = str_replace('{codigo}', $this->sku, $pattern);

        return $base.'/'.trim($this->familia, '/').'/'.ltrim($filename, '/');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('name', 'ilike', "%{$term}%")
                ->orWhere('description', 'ilike', "%{$term}%")
                ->orWhere('sku', 'ilike', "%{$term}%")
                ->orWhere('familia', 'ilike', "%{$term}%");
        });
    }
}
