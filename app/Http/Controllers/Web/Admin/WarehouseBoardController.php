<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseBoardController extends Controller
{
    /** Pedidos activos en cola de bodega. */
    private const BOARD_STATUSES = ['paid', 'processing'];

    public function index(): View
    {
        return view('admin.warehouse.board');
    }

    public function feed(): JsonResponse
    {
        $orders = Order::query()
            ->with(['items' => fn ($q) => $q->orderBy('id')])
            ->whereIn('status', self::BOARD_STATUSES)
            ->where('payment_status', 'paid')
            ->orderByRaw("CASE WHEN status = 'paid' THEN 0 ELSE 1 END")
            ->orderBy('created_at')
            ->limit(60)
            ->get()
            ->map(fn (Order $order) => $this->serializeOrder($order))
            ->values();

        return response()->json([
            'orders' => $orders,
            'server_time' => now()->timezone(config('app.timezone'))->toIso8601String(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:processing,shipped'],
        ]);

        if (! in_array($order->status, self::BOARD_STATUSES, true)) {
            return response()->json([
                'message' => 'Este pedido ya no está en la cola de bodega.',
            ], 422);
        }

        $next = $data['status'];

        if ($order->status === 'paid' && $next !== 'processing') {
            return response()->json([
                'message' => 'Un pedido nuevo solo puede pasar a preparación.',
            ], 422);
        }

        if ($order->status === 'processing' && $next !== 'shipped') {
            return response()->json([
                'message' => 'Un pedido en preparación solo puede marcarse como despachado.',
            ], 422);
        }

        if ($order->status !== $next) {
            $order->recordStatus($next, $order->status, 'Actualizado desde tablero de bodega');
        }

        $order->refresh()->load(['items' => fn ($q) => $q->orderBy('id')]);

        return response()->json([
            'order' => in_array($order->status, self::BOARD_STATUSES, true)
                ? $this->serializeOrder($order)
                : null,
            'removed' => ! in_array($order->status, self::BOARD_STATUSES, true),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrder(Order $order): array
    {
        $tz = config('app.timezone');
        $created = $order->created_at?->timezone($tz);

        $street = trim(($order->shipping_street ?? '').' '.($order->shipping_street_number ?? ''));
        if ($order->shipping_apartment) {
            $street .= ', Depto '.$order->shipping_apartment;
        }

        return [
            'id' => $order->id,
            'uuid' => $order->uuid,
            'code' => strtoupper(substr((string) $order->uuid, 0, 8)),
            'status' => $order->status,
            'status_label' => order_status_label($order->status),
            'created_at' => $created?->toIso8601String(),
            'created_at_label' => $created?->format('H:i') ?? '—',
            'created_at_full' => $created?->format('d/m/Y H:i') ?? '—',
            'customer_name' => $order->customer_name ?: $order->shipping_recipient_name ?: 'Cliente',
            'shipping_phone' => $order->shipping_phone,
            'shipping_comuna' => $order->shipping_comuna,
            'shipping_region' => $order->shipping_region,
            'shipping_address' => $street,
            'items_count' => $order->items->sum('quantity'),
            'total_label' => clp($order->total),
            'items' => $order->items->map(fn ($item) => [
                'name' => $item->product_name,
                'sku' => $item->product_sku,
                'quantity' => (int) $item->quantity,
            ])->values()->all(),
        ];
    }
}
