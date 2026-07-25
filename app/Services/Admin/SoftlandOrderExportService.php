<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SoftlandOrderExportService
{
    public function download(Order $order): StreamedResponse
    {
        $order->loadMissing(['items.product']);

        $contenido = $order->items
            ->map(fn (OrderItem $item) => $this->filaSoftland($order, $item))
            ->implode("\n");

        $nombre = 'NOTA_PEDIDO_'.$order->id.'_'.now()->format('YmdHis').'.TXT';

        return response()->streamDownload(
            static function () use ($contenido) {
                echo $contenido;
            },
            $nombre,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }

    public function contenido(Order $order): string
    {
        $order->loadMissing(['items.product']);

        return $order->items
            ->map(fn (OrderItem $item) => $this->filaSoftland($order, $item))
            ->implode("\n");
    }

    private function filaSoftland(Order $order, OrderItem $item): string
    {
        $tz = config('app.timezone');
        $fecha = $order->created_at?->timezone($tz)->format('d-m-Y') ?? now()->format('d-m-Y');
        $rut = trim((string) ($order->billing_rut ?? ''));
        $softland = $this->codigoSoftland($item);
        $descripcion = 'Pedido web #'.strtoupper(substr((string) $order->uuid, 0, 8));
        $bodega = (string) config('softland.codigo_bodega', '01');
        $precio = (string) (int) round((float) $item->unit_price);

        $campos = [
            (string) $order->id,
            $this->entreComillas($fecha),
            $this->entreComillas(''),
            '',
            $this->entreComillas(''),
            $this->entreComillas($rut),
            $this->entreComillas($bodega),
            '',
            '',
            $this->entreComillas($descripcion),
            '',
            '',
            $this->entreComillas($rut),
            '',
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
            '',
            $this->entreComillas($softland),
            '',
            (string) (int) $item->quantity,
            $precio,
            '', '', '', '', '', '', '', '', '',
            '', '', '',
            $this->entreComillas(trim((string) $item->product_name)),
            '', '', '', '',
        ];

        return implode(';', $campos);
    }

    private function codigoSoftland(OrderItem $item): string
    {
        $product = $item->product;

        if ($product instanceof Product) {
            $meta = $product->metadata ?? [];
            foreach (['softland', 'prod_item_softland'] as $key) {
                $value = trim((string) ($meta[$key] ?? ''));
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return trim((string) $item->product_sku);
    }

    private function entreComillas(string $valor): string
    {
        return '"'.str_replace('"', '""', $valor).'"';
    }
}
