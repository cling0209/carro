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

    public function test_search_suggestions_prefer_reysol_brand(): void
    {
        config(['products.preferred_brands' => ['Reysol']]);

        Product::create([
            'sku' => 'OTRO-002',
            'name' => 'Pincel sintético Artel',
            'slug' => 'pincel-sintetico-artel-2',
            'price' => 1990,
            'stock' => 10,
            'is_active' => true,
            'is_featured' => false,
        ]);

        Product::create([
            'sku' => 'REY-002',
            'name' => 'Reysol Pincel sintético plano',
            'slug' => 'reysol-pincel-sintetico-plano-2',
            'price' => 2490,
            'stock' => 10,
            'is_active' => true,
            'is_featured' => false,
        ]);

        $response = $this->getJson(route('catalog.suggest', ['q' => 'pincel']));

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Reysol Pincel sintético plano');
        $response->assertJsonPath('data.1.name', 'Pincel sintético Artel');
    }

    public function test_search_suggestions_require_two_characters(): void
    {
        $response = $this->getJson(route('catalog.suggest', ['q' => 'p']));

        $response->assertOk();
        $response->assertExactJson(['data' => []]);
    }

    public function test_search_matches_without_accents_and_prefers_reysol(): void
    {
        config(['products.preferred_brands' => ['Reysol']]);

        Product::create([
            'sku' => 'OTRO-003',
            'name' => 'Plumón permanente Artel',
            'slug' => 'plumon-permanente-artel',
            'price' => 990,
            'stock' => 10,
            'is_active' => true,
            'is_featured' => false,
        ]);

        Product::create([
            'sku' => 'REY-003',
            'name' => 'Reysol Plumón permanente negro',
            'slug' => 'reysol-plumon-permanente-negro',
            'price' => 1290,
            'stock' => 10,
            'is_active' => true,
            'is_featured' => false,
        ]);

        $response = $this->getJson(route('catalog.suggest', ['q' => 'plumon']));

        $response->assertOk();
        $response->assertJsonPath('data.0.name', 'Reysol Plumón permanente negro');
        $response->assertJsonPath('data.1.name', 'Plumón permanente Artel');
    }
}
