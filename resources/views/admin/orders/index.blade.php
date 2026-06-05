@extends('layouts.admin')

@section('title', 'Ventas')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Ventas realizadas</h1>
        <p class="text-muted mb-0">Pedidos y pagos confirmados en la tienda.</p>
    </div>

    <div class="card admin-card mb-4">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Buscar</label>
                    <input type="search" name="q" class="form-control" placeholder="UUID, email, cliente..."
                           value="{{ request('q') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Estado pedido</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        @foreach(['pending_payment','paid','processing','shipped','delivered','cancelled','payment_failed'] as $st)
                            <option value="{{ $st }}" @selected(request('status') === $st)>{{ order_status_label($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Estado pago</label>
                    <select name="payment_status" class="form-select">
                        <option value="">Todos</option>
                        @foreach(['pending','paid','failed','refunded'] as $ps)
                            <option value="{{ $ps }}" @selected(request('payment_status') === $ps)>{{ payment_status_label($ps) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card admin-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 admin-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Pedido</th>
                        <th>Cliente</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Pago</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="text-nowrap small">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                            <td><code class="small">{{ substr($order->uuid, 0, 13) }}</code></td>
                            <td>
                                <div>{{ $order->customer_name }}</div>
                                <small class="text-muted">{{ $order->customer_email }}</small>
                            </td>
                            <td>{{ $order->items_count }}</td>
                            <td class="fw-semibold">{{ clp($order->total) }}</td>
                            <td>
                                <span class="badge {{ $order->payment_status === 'paid' ? 'text-bg-success' : 'text-bg-warning' }}">
                                    {{ payment_status_label($order->payment_status) }}
                                </span>
                            </td>
                            <td>{{ order_status_label($order->status) }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No hay ventas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
