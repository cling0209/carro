<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Admin\SoftlandOrderExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftlandOrderExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_archivo_softland_usa_codigo_metadata_y_formato_cotiz(): void
    {
        $category = Category::query()->create([
            'name' => 'Papel',
            'slug' => 'papel',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'DEMO001',
            'name' => 'Papel bond A4',
            'slug' => 'papel-bond-a4',
            'price' => 1990,
            'stock' => 10,
            'is_active' => true,
            'metadata' => ['softland' => 'SL-DEMO001'],
        ]);

        $order = Order::query()->create([
            'status' => 'paid',
            'payment_status' => 'paid',
            'subtotal' => 3980,
            'shipping_amount' => 0,
            'total' => 3980,
            'shipping_recipient_name' => 'Cliente Test',
            'shipping_phone' => '912345678',
            'shipping_region' => 'Región Metropolitana',
            'shipping_comuna' => 'Santiago',
            'shipping_street' => 'Calle Falsa',
            'customer_email' => 'cliente@test.local',
            'customer_name' => 'Cliente Test',
            'document_type' => 'factura',
            'billing_rut' => '76.356.855-5',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Papel bond A4',
            'product_sku' => 'DEMO001',
            'quantity' => 2,
            'unit_price' => 1990,
            'line_total' => 3980,
        ]);

        $contenido = app(SoftlandOrderExportService::class)->contenido($order->fresh(['items.product']));

        $this->assertStringContainsString((string) $order->id.';', $contenido);
        $this->assertStringContainsString('"76.356.855-5"', $contenido);
        $this->assertStringContainsString('"01"', $contenido);
        $this->assertStringContainsString('"SL-DEMO001"', $contenido);
        $this->assertStringContainsString(';2;1990;', $contenido);
        $this->assertStringContainsString('"Papel bond A4"', $contenido);
        $this->assertStringContainsString('Pedido web #', $contenido);
    }

    public function test_admin_puede_descargar_archivo_softland(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $category = Category::query()->create([
            'name' => 'Papel',
            'slug' => 'papel-2',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'sku' => 'DEMO002',
            'name' => 'Producto',
            'slug' => 'producto-demo-2',
            'price' => 1000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $order = Order::query()->create([
            'status' => 'paid',
            'payment_status' => 'paid',
            'subtotal' => 1000,
            'shipping_amount' => 0,
            'total' => 1000,
            'shipping_recipient_name' => 'Cliente',
            'shipping_phone' => '912345678',
            'shipping_region' => 'Región Metropolitana',
            'shipping_comuna' => 'Santiago',
            'shipping_street' => 'Calle',
            'customer_email' => 'a@b.cl',
            'customer_name' => 'Cliente',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => 'Producto',
            'product_sku' => 'DEMO002',
            'quantity' => 1,
            'unit_price' => 1000,
            'line_total' => 1000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.export.softland', $order));

        $response->assertOk();
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('NOTA_PEDIDO_'.$order->id, (string) $response->headers->get('content-disposition'));
        $this->assertStringContainsString('"DEMO002"', $response->streamedContent());
    }
}
