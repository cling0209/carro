<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchPreferredBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_results_prefer_reysol_brand(): void
    {
        config(['products.preferred_brands' => ['Reysol']]);

        Product::create([
            'sku' => 'OTRO-001',
            'name' => 'Pincel sintético Artel',
            'slug' => 'pincel-sintetico-artel',
            'price' => 1990,
            'stock' => 10,
            'is_active' => true,
            'is_featured' => false,
        ]);

        Product::create([
            'sku' => 'REY-001',
            'name' => 'Reysol Pincel sintético plano',
            'slug' => 'reysol-pincel-sintetico-plano',
            'price' => 2490,
            'stock' => 10,
            'is_active' => true,
            'is_featured' => false,
        ]);

        $response = $this->get(route('catalog', ['q' => 'pincel']));

        $response->assertOk();
        $response->assertSeeInOrder([
            'Reysol Pincel sintético plano',
            'Pincel sintético Artel',
        ]);
    }
}
