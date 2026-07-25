<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'category_id', 'sku', 'familia', 'image_filename', 'name', 'slug', 'description',
        'price', 'compare_at_price', 'stock', 'weight_kg', 'is_active', 'is_featured', 'metadata',
    ];

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_at_price' => 'integer',
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
        $candidates = $this->imageUrlCandidates();

        if ($candidates !== []) {
            return $candidates[0];
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

    /**
     * @return list<string>
     */
    public function imageUrlCandidates(): array
    {
        $base = rtrim((string) config('products.image_base_url'), '/');
        $folder = trim((string) $this->familia);

        if ($base === '' || $folder === '') {
            return [];
        }

        $filenames = $this->imageFilenameCandidates();

        if ($filenames === []) {
            return [];
        }

        $urls = [];

        foreach ($filenames as $filename) {
            $urls[] = $base.'/'.trim($folder, '/').'/'.ltrim($filename, '/');
        }

        return array_values(array_unique($urls));
    }

    public function buildExternalImageUrl(): ?string
    {
        $candidates = $this->imageUrlCandidates();

        return $candidates[0] ?? null;
    }

    /**
     * @return list<string>
     */
    private function imageFilenameCandidates(): array
    {
        $primary = trim((string) $this->image_filename);

        if ($primary === '') {
            return [];
        }

        $candidates = [$primary];

        if (preg_match('/^(.+)_medium(\.[^.]+)$/i', $primary, $matches)) {
            $candidates[] = $matches[1].$matches[2];
        } elseif (preg_match('/^(.+)(\.[^.]+)$/i', $primary, $matches)) {
            $candidates[] = $matches[1].'_medium'.$matches[2];
        }

        return array_values(array_unique($candidates));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('category_id')
                    ->orWhereHas('category');
            });
    }

    public function archive(): void
    {
        $this->update(['is_active' => false]);
        $this->delete();
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

        $like = '%'.self::foldAccents($term).'%';
        $nameFolded = self::accentFoldSql('products.name');
        $descriptionFolded = self::accentFoldSql('products.description');
        $skuFolded = self::accentFoldSql('products.sku');

        return $query->where(function (Builder $q) use ($like, $nameFolded, $descriptionFolded, $skuFolded) {
            $q->whereRaw("{$nameFolded} LIKE ?", [$like])
                ->orWhereRaw("{$descriptionFolded} LIKE ?", [$like])
                ->orWhereRaw("{$skuFolded} LIKE ?", [$like])
                ->orWhereHas('category', function (Builder $cat) use ($like) {
                    $catName = self::accentFoldSql('categories.name');
                    $catSlug = self::accentFoldSql('categories.slug');
                    $cat->whereRaw("{$catName} LIKE ?", [$like])
                        ->orWhereRaw("{$catSlug} LIKE ?", [$like]);
                });
        });
    }

    /**
     * Prioriza productos de marcas preferidas (p. ej. Reysol) en resultados de búsqueda.
     */
    public function scopeOrderByPreferredBrands(Builder $query): Builder
    {
        /** @var list<string> $brands */
        $brands = config('products.preferred_brands', []);

        if ($brands === []) {
            return $query;
        }

        $nameFolded = self::accentFoldSql('products.name');
        $descriptionFolded = self::accentFoldSql('products.description');
        $attrNameFolded = self::accentFoldSql('pa.name');
        $attrValueFolded = self::accentFoldSql('pa.value');
        $conditions = [];
        $bindings = [];

        foreach ($brands as $brand) {
            $like = '%'.self::foldAccents($brand).'%';
            $conditions[] = "({$nameFolded} LIKE ? OR {$descriptionFolded} LIKE ? OR EXISTS (
                SELECT 1 FROM product_attributes pa
                WHERE pa.product_id = products.id
                  AND {$attrNameFolded} LIKE ?
                  AND {$attrValueFolded} LIKE ?
            ))";
            array_push($bindings, $like, $like, self::foldAccents('marca'), $like);
        }

        return $query->orderByRaw(
            'CASE WHEN ('.implode(' OR ', $conditions).') THEN 0 ELSE 1 END',
            $bindings
        );
    }

    public static function foldAccents(string $value): string
    {
        $folded = strtr(mb_strtolower($value), self::accentMap());

        return $folded;
    }

    /**
     * SQL expression that lowercases and strips common Spanish accents.
     */
    protected static function accentFoldSql(string $column): string
    {
        $expr = "lower(coalesce({$column}, ''))";

        foreach (self::accentMap() as $from => $to) {
            $expr = "replace({$expr}, '{$from}', '{$to}')";
        }

        return $expr;
    }

    /**
     * @return array<string, string>
     */
    protected static function accentMap(): array
    {
        return [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ];
    }
}
