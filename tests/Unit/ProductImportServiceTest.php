<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_new_product_from_csv(): void
    {
        $category = Category::create([
            'name' => 'Electrónica',
            'slug' => 'electronica',
            'is_active' => true,
        ]);

        $csv = "sku;nombre;precio;stock;categoria_slug\n";
        $csv .= 'IMP-001;Producto importado;19990;10;electronica';

        $file = UploadedFile::fake()->createWithContent('productos.csv', "\xEF\xBB\xBF".$csv);

        $result = app(ProductImportService::class)->importFromUploadedFile($file);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertDatabaseHas('products', [
            'sku' => 'IMP-001',
            'name' => 'Producto importado',
            'category_id' => $category->id,
            'stock' => 10,
        ]);
    }

    public function test_updates_existing_product_by_sku(): void
    {
        $product = Product::create([
            'sku' => 'IMP-002',
            'name' => 'Antes',
            'slug' => 'antes',
            'price' => 1000,
            'stock' => 1,
            'is_active' => true,
        ]);

        $csv = "sku;nombre;precio;stock\nIMP-002;Después;5000;9";
        $file = UploadedFile::fake()->createWithContent('productos.csv', $csv);

        $result = app(ProductImportService::class)->importFromUploadedFile($file);

        $this->assertSame(1, $result['updated']);
        $product->refresh();
        $this->assertSame('Después', $product->name);
        $this->assertSame(9, $product->stock);
    }
}
