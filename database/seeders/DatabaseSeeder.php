<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@carro.local'],
            ['name' => 'Administrador', 'password' => 'Admin123!Secure', 'role' => 'admin']
        );

        User::query()->firstOrCreate(
            ['email' => 'cliente@carro.local'],
            ['name' => 'Cliente Demo', 'password' => 'Cliente123!', 'role' => 'customer']
        );

        $this->call(ShippingSeeder::class);

        if (Category::query()->exists()) {
            return;
        }

        $electronica = Category::create([
            'name' => 'Electrónica',
            'slug' => 'electronica',
            'description' => 'Tecnología y gadgets',
            'sort_order' => 1,
        ]);

        $hogar = Category::create([
            'name' => 'Hogar',
            'slug' => 'hogar',
            'description' => 'Todo para tu casa',
            'sort_order' => 2,
        ]);

        $moda = Category::create([
            'name' => 'Moda',
            'slug' => 'moda',
            'description' => 'Ropa y accesorios',
            'sort_order' => 3,
        ]);

        $this->seedProduct($electronica->id, 'AUD-001', 'Audífonos Bluetooth Pro', 'audifonos-bluetooth-pro', 29990, 39990, 45, 0.35, true, [
            ['name' => 'Color', 'value' => 'Negro'],
            ['name' => 'Conectividad', 'value' => 'Bluetooth 5.3'],
        ]);

        $this->seedProduct($electronica->id, 'TAB-002', 'Tablet 10" Full HD', 'tablet-10-full-hd', 149990, null, 20, 0.55, true, [
            ['name' => 'Pantalla', 'value' => '10.1"'],
            ['name' => 'Almacenamiento', 'value' => '128 GB'],
        ]);

        $this->seedProduct($hogar->id, 'CAF-003', 'Cafetera Espresso Automática', 'cafetera-espresso', 89990, 109990, 15, 4.2, false, [
            ['name' => 'Presión', 'value' => '15 bar'],
        ]);

        $this->seedProduct($moda->id, 'POL-004', 'Polera Algodón Premium', 'polera-algodon-premium', 12990, 15990, 100, 0.25, true, [
            ['name' => 'Talla', 'value' => 'M'],
            ['name' => 'Material', 'value' => '100% Algodón'],
        ]);

        $this->seedProduct($moda->id, 'ZAP-005', 'Zapatillas Urban Runner', 'zapatillas-urban-runner', 45990, null, 35, 0.8, true, [
            ['name' => 'Talla', 'value' => '42'],
            ['name' => 'Color', 'value' => 'Blanco'],
        ]);
    }

    protected function seedProduct(
        int $categoryId,
        string $sku,
        string $name,
        string $slug,
        float $price,
        ?float $compareAt,
        int $stock,
        ?float $weightKg,
        bool $featured,
        array $attributes = [],
    ): void {
        $product = Product::create([
            'category_id' => $categoryId,
            'sku' => $sku,
            'name' => $name,
            'slug' => $slug,
            'description' => "Descripción de {$name}. Producto de demostración Carro.",
            'price' => $price,
            'compare_at_price' => $compareAt,
            'stock' => $stock,
            'weight_kg' => $weightKg,
            'is_active' => true,
            'is_featured' => $featured,
        ]);

        foreach ($attributes as $attr) {
            ProductAttribute::create([
                'product_id' => $product->id,
                'name' => $attr['name'],
                'value' => $attr['value'],
            ]);
        }
    }
}
