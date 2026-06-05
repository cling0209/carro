@extends('layouts.admin')

@section('title', 'Envíos')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h1 class="h3 fw-bold mb-1">Configuración de envíos</h1>
        <p class="text-muted mb-0">
            Región Metropolitana: tarifa fija. Otras regiones: según peso total del carrito.
        </p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card admin-card">
                <div class="card-header bg-white fw-semibold">Tarifas generales (RM)</div>
                <div class="card-body">
                    <form method="post" action="{{ route('admin.shipping.settings') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Tarifa fija RM (CLP) *</label>
                            <input type="number" name="rm_flat_rate" min="0" step="1"
                                   class="form-control @error('rm_flat_rate') is-invalid @enderror"
                                   value="{{ old('rm_flat_rate', $rmFlatRate) }}" required>
                            @error('rm_flat_rate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Aplica a Región Metropolitana de Santiago.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Peso por defecto producto (kg) *</label>
                            <input type="number" name="default_product_weight_kg" min="0.001" step="0.001"
                                   class="form-control @error('default_product_weight_kg') is-invalid @enderror"
                                   value="{{ old('default_product_weight_kg', $defaultProductWeight) }}" required>
                            @error('default_product_weight_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Si un producto no tiene peso definido.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Guardar configuración</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card admin-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Tramos por peso (otras regiones)</span>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#rateModal"
                            onclick="openRateModal()">
                        <i class="bi bi-plus-lg"></i> Nuevo tramo
                    </button>
                </div>
                <div class="card-body border-bottom py-3 text-muted small">
                    Fuera de la Región Metropolitana el costo depende del peso total del carrito.
                    Puedes editar, agregar o desactivar tramos según necesites.
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle admin-table">
                        <thead>
                            <tr>
                                <th>Etiqueta</th>
                                <th>Rango (kg)</th>
                                <th class="text-end">Precio</th>
                                <th class="text-center">Orden</th>
                                <th class="text-center">Activo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rates as $rate)
                                <tr>
                                    <td>{{ $rate->label }}</td>
                                    <td>
                                        {{ number_format($rate->min_weight_kg, 2, ',', '.') }}
                                        –
                                        @if($rate->max_weight_kg !== null)
                                            {{ number_format($rate->max_weight_kg, 2, ',', '.') }}
                                        @else
                                            ∞
                                        @endif
                                    </td>
                                    <td class="text-end">{{ clp($rate->price) }}</td>
                                    <td class="text-center">{{ $rate->sort_order }}</td>
                                    <td class="text-center">
                                        @if($rate->is_active)
                                            <span class="badge text-bg-success">Sí</span>
                                        @else
                                            <span class="badge text-bg-secondary">No</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary"
                                                onclick='openRateModal(@json($rate))'>
                                            Editar
                                        </button>
                                        <form method="post" action="{{ route('admin.shipping.rates.destroy', $rate) }}"
                                              class="d-inline" onsubmit="return confirm('¿Eliminar este tramo?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No hay tramos configurados. Agrega al menos uno para envíos fuera de RM.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="rateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rateForm" method="post" action="{{ route('admin.shipping.rates.store') }}">
                @csrf
                <input type="hidden" name="_method" id="rateFormMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="rateModalTitle">Nuevo tramo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Etiqueta *</label>
                        <input type="text" name="label" id="rateLabel" class="form-control" required
                               placeholder="Ej. Hasta 1 kg">
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Peso mínimo (kg) *</label>
                            <input type="number" name="min_weight_kg" id="rateMin" min="0" step="0.001"
                                   class="form-control" value="0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Peso máximo (kg)</label>
                            <input type="number" name="max_weight_kg" id="rateMax" min="0" step="0.001"
                                   class="form-control" placeholder="Vacío = sin límite">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Precio (CLP) *</label>
                            <input type="number" name="price" id="ratePrice" min="0" step="1"
                                   class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Orden</label>
                            <input type="number" name="sort_order" id="rateSort" min="0" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="rateActive" value="1" checked>
                        <label class="form-check-label" for="rateActive">Tramo activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar tramo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const rateStoreUrl = @json(route('admin.shipping.rates.store'));
const rateUpdateUrlTemplate = @json(route('admin.shipping.rates.update', ['rate' => 0]));

function openRateModal(rate = null) {
    const form = document.getElementById('rateForm');
    const method = document.getElementById('rateFormMethod');
    document.getElementById('rateModalTitle').textContent = rate ? 'Editar tramo' : 'Nuevo tramo';

    if (rate) {
        form.action = rateUpdateUrlTemplate.replace(/\/0$/, '/' + rate.id);
        method.value = 'PUT';
        document.getElementById('rateLabel').value = rate.label;
        document.getElementById('rateMin').value = rate.min_weight_kg;
        document.getElementById('rateMax').value = rate.max_weight_kg ?? '';
        document.getElementById('ratePrice').value = rate.price;
        document.getElementById('rateSort').value = rate.sort_order;
        document.getElementById('rateActive').checked = !!rate.is_active;
    } else {
        form.action = rateStoreUrl;
        method.value = 'POST';
        form.reset();
        document.getElementById('rateActive').checked = true;
        document.getElementById('rateSort').value = 0;
        document.getElementById('rateMin').value = 0;
    }
}
</script>
@endpush
