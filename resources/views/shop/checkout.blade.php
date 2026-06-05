@extends('layouts.shop')

@section('title', 'Checkout')

@section('content')
<section class="container py-4 py-lg-5">
    <h1 class="h3 fw-bold mb-4">Finalizar compra</h1>

    <form action="{{ route('checkout.store') }}" method="post">
        @csrf
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="checkout-card card p-4 mb-4">
                    <h2 class="h5 fw-bold mb-3">Datos de contacto</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre completo *</label>
                            <input type="text" name="customer_name" class="form-control @error('customer_name') is-invalid @enderror"
                                   value="{{ old('customer_name') }}" required>
                            @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="checkout-card card p-4">
                    <h2 class="h5 fw-bold mb-3">Dirección de envío</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Destinatario *</label>
                            <input type="text" name="recipient_name" class="form-control @error('recipient_name') is-invalid @enderror"
                                   value="{{ old('recipient_name') }}" required>
                            @error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono *</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone') }}" placeholder="+56 9..." required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Región *</label>
                            <select name="region" id="region" class="form-select @error('region') is-invalid @enderror" required>
                                <option value="">Selecciona región</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region['region'] }}" @selected(old('region') === $region['region'])>{{ $region['region'] }}</option>
                                @endforeach
                            </select>
                            @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Comuna *</label>
                            <select name="comuna" id="comuna" class="form-select @error('comuna') is-invalid @enderror" required>
                                <option value="">Selecciona comuna</option>
                            </select>
                            @error('comuna')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Calle *</label>
                            <input type="text" name="street" class="form-control @error('street') is-invalid @enderror"
                                   value="{{ old('street') }}" required>
                            @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Número</label>
                            <input type="text" name="street_number" class="form-control" value="{{ old('street_number') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Depto</label>
                            <input type="text" name="apartment" class="form-control" value="{{ old('apartment') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="checkout-card card p-4 sticky-top" style="top:5rem">
                    <h2 class="h5 fw-bold mb-3">Tu pedido</h2>
                    <ul class="list-unstyled mb-3">
                        @foreach($formatted['items'] as $item)
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span>{{ $item['product']['name'] }} × {{ $item['quantity'] }}</span>
                                <span>{{ clp($item['line_total']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span id="summary-subtotal">{{ clp($formatted['subtotal']) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 text-muted small">
                        <span>Envío</span>
                        <span id="summary-shipping">Selecciona región y comuna</span>
                    </div>
                    <div id="shipping-detail" class="small text-muted mb-3 d-none"></div>
                    <div class="d-flex justify-content-between fs-5 fw-bold mb-4 border-top pt-3">
                        <span>Total</span>
                        <span class="text-primary" id="summary-total">{{ clp($formatted['subtotal']) }}</span>
                    </div>
                    <div id="shipping-error" class="alert alert-warning small d-none"></div>
                    <div class="alert alert-info small">
                        <i class="bi bi-credit-card"></i> Serás redirigido a <strong>Webpay Plus</strong> para pagar con tarjeta de crédito o débito.
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill w-100" id="checkout-submit">
                        Pagar con Webpay <i class="bi bi-lock-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script>
const regions = @json($regions);
const regionSelect = document.getElementById('region');
const comunaSelect = document.getElementById('comuna');
const oldComuna = @json(old('comuna'));
const quoteUrl = @json(route('checkout.shipping.quote'));
const subtotalAmount = {{ (float) $formatted['subtotal'] }};

const summaryShipping = document.getElementById('summary-shipping');
const summaryTotal = document.getElementById('summary-total');
const shippingDetail = document.getElementById('shipping-detail');
const shippingError = document.getElementById('shipping-error');
const checkoutSubmit = document.getElementById('checkout-submit');

function formatClp(amount) {
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(amount);
}

function isRmRegion(regionName) {
    return regionName.toLowerCase().includes('metropolitana');
}

function loadComunas() {
    const regionName = regionSelect.value;
    comunaSelect.innerHTML = '<option value="">Selecciona comuna</option>';
    const region = regions.find(r => r.region === regionName);
    if (!region) {
        quoteShipping();
        return;
    }
    region.comunas.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c;
        opt.textContent = c;
        if (c === oldComuna) opt.selected = true;
        comunaSelect.appendChild(opt);
    });
    quoteShipping();
}

async function quoteShipping() {
    const region = regionSelect.value;
    const comuna = comunaSelect.value;
    shippingError.classList.add('d-none');
    shippingDetail.classList.add('d-none');

    if (!region) {
        summaryShipping.textContent = 'Selecciona región y comuna';
        summaryTotal.textContent = formatClp(subtotalAmount);
        checkoutSubmit.disabled = false;
        return;
    }

    if (!isRmRegion(region) && !comuna) {
        summaryShipping.textContent = 'Selecciona comuna';
        summaryTotal.textContent = formatClp(subtotalAmount);
        checkoutSubmit.disabled = true;
        return;
    }

    summaryShipping.textContent = 'Calculando...';

    try {
        const params = new URLSearchParams({ region });
        if (comuna) params.set('comuna', comuna);
        const response = await fetch(`${quoteUrl}?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'No se pudo calcular el envío.');
        }

        summaryShipping.textContent = formatClp(data.shipping.amount);
        summaryTotal.textContent = formatClp(data.total);

        let detail = data.shipping.rate_label;
        if (data.shipping.zone === 'rm') {
            detail = 'Tarifa fija Región Metropolitana';
        } else if (data.shipping.zone === 'regions' && data.shipping.metadata) {
            const meta = data.shipping.metadata;
            detail = `${meta.comuna}: fija ${formatClp(meta.region_flat_rate)} + tramo ${formatClp(meta.weight_tramo_amount)}`;
            detail += ` · ${Number(data.shipping.total_weight_kg).toFixed(2)} kg`;
        }
        shippingDetail.textContent = detail;
        shippingDetail.classList.remove('d-none');
        checkoutSubmit.disabled = false;
    } catch (error) {
        summaryShipping.textContent = '—';
        summaryTotal.textContent = formatClp(subtotalAmount);
        shippingError.textContent = error.message;
        shippingError.classList.remove('d-none');
        checkoutSubmit.disabled = true;
    }
}

regionSelect.addEventListener('change', loadComunas);
comunaSelect.addEventListener('change', quoteShipping);
if (regionSelect.value) loadComunas();
</script>
@endpush
