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
            'name' => 'Libros',
            'slug' => 'lib',
            'is_active' => true,
        ]);

        $csv = "sku;nombre;precio;stock;familia\n";
        $csv .= 'IMP-001;Producto importado;19990;10;LIB';

        $file = UploadedFile::fake()->createWithContent('productos.csv', "\xEF\xBB\xBF".$csv);

        $result = app(ProductImportService::class)->importFromUploadedFile($file);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertDatabaseHas('products', [
            'sku' => 'IMP-001',
            'name' => 'Producto importado',
            'category_id' => $category->id,
            'familia' => 'LIB',
            'stock' => 10,
        ]);
    }

    public function test_updates_existing_product_by_sku(): void
    {
        $category = Category::create([
            'name' => 'Libros',
            'slug' => 'lib',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'IMP-002',
            'name' => 'Antes',
            'slug' => 'antes',
            'familia' => 'LIB',
            'price' => 1000,
            'stock' => 1,
            'is_active' => true,
        ]);

        $csv = "sku;nombre;precio;stock;familia\nIMP-002;Después;5000;9;LIB";
        $file = UploadedFile::fake()->createWithContent('productos.csv', $csv);

        $result = app(ProductImportService::class)->importFromUploadedFile($file);

        $this->assertSame(1, $result['updated']);
        $product->refresh();
        $this->assertSame('Después', $product->name);
        $this->assertSame(9, $product->stock);
    }

    public function test_imports_csv_saved_with_windows_latin1_encoding(): void
    {
        Category::create([
            'name' => 'Librería',
            'slug' => 'libr',
            'is_active' => true,
        ]);

        $line = 'IMP-003;Pizarra Acrílica Arcovi;19990;5;LIBR';
        $latin1 = mb_convert_encoding($line, 'Windows-1252', 'UTF-8');
        $csv = "sku;nombre;precio;stock;familia\n".$latin1;

        $file = UploadedFile::fake()->createWithContent('productos.csv', $csv);

        $result = app(ProductImportService::class)->importFromUploadedFile($file);

        $this->assertSame(1, $result['created']);
        $this->assertDatabaseHas('products', [
            'sku' => 'IMP-003',
            'name' => 'Pizarra Acrílica Arcovi',
        ]);
    }
}
