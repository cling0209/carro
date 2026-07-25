@extends('layouts.shop')

@section('title', 'Finalizar compra')

@section('content')
<section class="container py-4 py-lg-5">
    <h1 class="h3 fw-bold mb-4">Finalizar compra</h1>

    @if($isLoggedIn)
        <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <span>Hola, <strong>{{ $userName }}</strong>. Tus datos están precargados para esta compra.</span>
            <form method="post" action="{{ route('account.logout') }}" class="mb-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-success">Cerrar sesión</button>
            </form>
        </div>
    @else
        <div class="alert alert-light border mb-4">
            ¿Ya tienes cuenta?
            <a href="{{ route('account.login') }}">Ingresa aquí</a> para no volver a escribir tus datos.
        </div>
    @endif

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
                                   value="{{ $defaults['customer_name'] }}" required>
                            @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Correo electrónico *</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ $defaults['email'] }}" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="checkout-card card p-4 mb-4">
                    <h2 class="h5 fw-bold mb-3">Dirección de envío</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Destinatario (quien recibe la compra) *</label>
                            <input type="text" name="recipient_name" class="form-control @error('recipient_name') is-invalid @enderror"
                                   value="{{ $defaults['recipient_name'] }}" required>
                            @error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono *</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ $defaults['phone'] }}" placeholder="+56 9..." required>
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Región *</label>
                            <select name="region" id="region" class="form-select @error('region') is-invalid @enderror" required>
                                <option value="">Selecciona región</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region['region'] }}" @selected($defaults['region'] === $region['region'])>{{ $region['region'] }}</option>
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
                        <div class="col-12">
                            <label class="form-label">Ubicación en el mapa *</label>
                            <p class="form-text mt-0 mb-2">Marca el punto exacto donde quieres recibir el pedido. Sin pin no se puede pagar.</p>
                            <div id="checkout-map" class="checkout-map" role="application" aria-label="Mapa de ubicación de envío"></div>
                            <div id="checkout-map-status" class="small mt-2 text-muted">
                                Selecciona región y comuna, luego toca el mapa para marcar tu ubicación.
                            </div>
                            @error('latitude')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <input type="hidden" name="latitude" id="latitude" value="{{ $defaults['latitude'] }}" required>
                            <input type="hidden" name="longitude" id="longitude" value="{{ $defaults['longitude'] }}" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Calle *</label>
                            <input type="text" name="street" id="street" class="form-control @error('street') is-invalid @enderror"
                                   value="{{ $defaults['street'] }}" required>
                            @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Número</label>
                            <input type="text" name="street_number" id="street_number" class="form-control" value="{{ $defaults['street_number'] }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Depto</label>
                            <input type="text" name="apartment" class="form-control" value="{{ $defaults['apartment'] }}">
                        </div>
                    </div>
                </div>

                @unless($isLoggedIn)
                    <div class="checkout-card card p-4">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="create_account" id="create_account"
                                   value="1" @checked(old('create_account'))>
                            <label class="form-check-label fw-semibold" for="create_account">
                                Crear cuenta con estos datos
                            </label>
                            <div class="form-text">En tu próxima compra no tendrás que volver a completar el formulario.</div>
                        </div>
                        <div id="create-account-fields" class="row g-3 mt-3 @unless(old('create_account')) d-none @endunless">
                            <div class="col-md-6">
                                <label class="form-label" for="password">Contraseña *</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password"
                                           class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                                    <button type="button" class="btn btn-outline-secondary js-password-toggle"
                                            data-target="password" aria-label="Mostrar contraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Confirmar contraseña *</label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                           class="form-control" autocomplete="new-password">
                                    <button type="button" class="btn btn-outline-secondary js-password-toggle"
                                            data-target="password_confirmation" aria-label="Mostrar contraseña">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endunless
            </div>

            <div class="col-lg-5">
                <div class="checkout-card card p-4 sticky-top" style="top:5rem">
                    <h2 class="h5 fw-bold mb-3">Tu pedido</h2>
                    <ul class="list-unstyled mb-3">
                        @foreach($formatted['items'] as $item)
                            <li class="d-flex justify-content-between py-2 border-bottom">
                                <span>{{ data_get($item, 'product.name', 'Producto') }} × {{ $item['quantity'] }}</span>
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
                    <div class="d-flex justify-content-between fs-5 fw-bold mb-4 border-top pt-3">
                        <span>Total</span>
                        <span class="text-primary" id="summary-total">—</span>
                    </div>
                    <div id="shipping-error" class="alert alert-warning small d-none"></div>
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-credit-card"></i> Serás redirigido a <strong>Webpay Plus</strong> para pagar con tarjeta de crédito o débito.
                    </div>
                    <div id="checkout-submit-hint" class="alert alert-warning border-warning small mb-3">
                        <i class="bi bi-info-circle-fill me-1"></i>
                        El botón de pago se activará cuando ingreses todos los <strong>datos obligatorios</strong>, marques el <strong>pin en el mapa</strong> y se calcule el envío.
                    </div>
                    <button type="submit" class="btn btn-webpay-pay btn-lg rounded-pill w-100" id="checkout-submit" disabled>
                        Pagar con Webpay <i class="bi bi-lock-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </form>
</section>
@endsection

@push('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
const regions = @json($regions);
const regionSelect = document.getElementById('region');
const comunaSelect = document.getElementById('comuna');
const savedComuna = @json($defaults['comuna']);
const quoteUrl = @json(route('checkout.shipping.quote'));
const geocodeUrl = @json(route('checkout.geocode'));
const reverseGeocodeUrl = @json(route('checkout.reverse-geocode'));
const initialLat = @json(filled($defaults['latitude'] ?? null) ? (float) $defaults['latitude'] : null);
const initialLng = @json(filled($defaults['longitude'] ?? null) ? (float) $defaults['longitude'] : null);

const summaryShipping = document.getElementById('summary-shipping');
const summaryTotal = document.getElementById('summary-total');
const shippingError = document.getElementById('shipping-error');
const checkoutSubmit = document.getElementById('checkout-submit');
const checkoutSubmitHint = document.getElementById('checkout-submit-hint');
const checkoutForm = checkoutSubmit.closest('form');
const latInput = document.getElementById('latitude');
const lngInput = document.getElementById('longitude');
const streetInput = document.getElementById('street');
const streetNumberInput = document.getElementById('street_number');
const mapStatus = document.getElementById('checkout-map-status');

let shippingReady = false;
let mapReady = false;
let map = null;
let marker = null;
let centeringMap = false;

const createAccountCheckbox = document.getElementById('create_account');
const createAccountFields = document.getElementById('create-account-fields');
const passwordInput = document.getElementById('password');
const passwordConfirmInput = document.getElementById('password_confirmation');

function formatClp(amount) {
    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(amount);
}

function isRmRegion(regionName) {
    return regionName.toLowerCase().includes('metropolitana');
}

function pinIsSet() {
    return String(latInput.value || '').trim() !== '' && String(lngInput.value || '').trim() !== '';
}

function toggleCreateAccountFields() {
    if (!createAccountCheckbox || !createAccountFields) return;
    const show = createAccountCheckbox.checked;
    createAccountFields.classList.toggle('d-none', !show);
    if (passwordInput) passwordInput.required = show;
    if (passwordConfirmInput) passwordConfirmInput.required = show;
    updateCheckoutSubmitState();
}

function requiredFieldsComplete() {
    if (!checkoutForm) return false;

    return Array.from(checkoutForm.querySelectorAll('[required]')).every((field) => {
        if (field.closest('.d-none')) {
            return true;
        }

        if (field.type === 'hidden') {
            return String(field.value ?? '').trim() !== '';
        }

        if (field.offsetParent === null) {
            return true;
        }

        return String(field.value ?? '').trim() !== '';
    });
}

function updateCheckoutSubmitState() {
    const ready = shippingReady && pinIsSet() && requiredFieldsComplete();
    checkoutSubmit.disabled = !ready;

    if (checkoutSubmitHint) {
        checkoutSubmitHint.classList.toggle('d-none', ready);
    }
}

function setMapStatus(text, isError = false) {
    if (!mapStatus) return;
    mapStatus.textContent = text;
    mapStatus.classList.toggle('text-danger', isError);
    mapStatus.classList.toggle('text-success', !isError && pinIsSet());
    mapStatus.classList.toggle('text-muted', !isError && !pinIsSet());
}

function initMap() {
    if (typeof L === 'undefined') {
        setMapStatus('No se pudo cargar el mapa. Recarga la página e inténtalo de nuevo.', true);
        return;
    }

    map = L.map('checkout-map', { scrollWheelZoom: false }).setView([-33.4489, -70.6693], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    map.on('click', (event) => {
        setPin(event.latlng.lat, event.latlng.lng, true);
    });

    if (initialLat !== null && initialLng !== null) {
        setPin(initialLat, initialLng, false);
        map.setView([initialLat, initialLng], 16);
    }

    setTimeout(() => map.invalidateSize(), 150);
    mapReady = true;
}

function setPin(lat, lng, reverseFill) {
    const position = [lat, lng];

    if (!marker) {
        marker = L.marker(position, { draggable: true }).addTo(map);
        marker.on('dragend', () => {
            const p = marker.getLatLng();
            setPin(p.lat, p.lng, true);
        });
    } else {
        marker.setLatLng(position);
    }

    latInput.value = Number(lat).toFixed(7);
    lngInput.value = Number(lng).toFixed(7);
    setMapStatus('Ubicación marcada. Puedes arrastrar el pin para ajustar.');
    updateCheckoutSubmitState();

    if (reverseFill) {
        reverseGeocode(lat, lng);
    }
}

function clearPin({ keepMapCenter = false } = {}) {
    if (marker) {
        map.removeLayer(marker);
        marker = null;
    }
    latInput.value = '';
    lngInput.value = '';
    if (!keepMapCenter) {
        setMapStatus('Selecciona región y comuna, luego toca el mapa para marcar tu ubicación.');
    }
    updateCheckoutSubmitState();
}

async function centerMapOnComuna() {
    const region = regionSelect.value;
    const comuna = comunaSelect.value;

    if (!region || !comuna || !mapReady) {
        return;
    }

    centeringMap = true;
    setMapStatus('Centrando mapa en ' + comuna + '...');

    try {
        const params = new URLSearchParams({ region, comuna });
        const response = await fetch(`${geocodeUrl}?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'No se pudo centrar el mapa.');
        }

        map.setView([data.lat, data.lng], 14);
        if (!pinIsSet()) {
            setMapStatus('Toca el mapa para marcar el punto de entrega en ' + comuna + '.');
        }
    } catch (error) {
        setMapStatus(error.message || 'No se pudo centrar el mapa.', true);
    } finally {
        centeringMap = false;
    }
}

async function reverseGeocode(lat, lng) {
    try {
        const params = new URLSearchParams({ lat, lng });
        const response = await fetch(`${reverseGeocodeUrl}?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'No se pudo leer la dirección del mapa.');
        }

        if (data.street && streetInput) {
            streetInput.value = data.street;
        }
        if (streetNumberInput && data.street_number) {
            streetNumberInput.value = data.street_number;
        }

        setMapStatus('Ubicación marcada. Revisa calle y número, y ajusta el pin si hace falta.');
        updateCheckoutSubmitState();
    } catch (error) {
        setMapStatus('Pin marcado. Completa calle y número manualmente si no se detectaron.', true);
    }
}

function loadComunas() {
    const regionName = regionSelect.value;
    const previousComuna = comunaSelect.value || savedComuna;
    comunaSelect.innerHTML = '<option value="">Selecciona comuna</option>';
    const region = regions.find(r => r.region === regionName);
    if (!region) {
        clearPin();
        quoteShipping();
        return;
    }
    region.comunas.forEach(c => {
        const name = typeof c === 'string' ? c : (c.nombre || '');
        if (!name) return;
        const opt = document.createElement('option');
        opt.value = name;
        opt.textContent = name;
        if (name === previousComuna) opt.selected = true;
        comunaSelect.appendChild(opt);
    });
    quoteShipping();
    if (comunaSelect.value) {
        if (!pinIsSet() || !initialLat) {
            clearPin({ keepMapCenter: true });
        }
        centerMapOnComuna();
    } else {
        clearPin();
    }
}

async function quoteShipping() {
    const region = regionSelect.value;
    const comuna = comunaSelect.value;
    shippingError.classList.add('d-none');

    if (!region) {
        summaryShipping.textContent = 'Selecciona región y comuna';
        summaryTotal.textContent = '—';
        shippingReady = false;
        updateCheckoutSubmitState();
        return;
    }

    if (!isRmRegion(region) && !comuna) {
        summaryShipping.textContent = 'Selecciona comuna';
        summaryTotal.textContent = '—';
        shippingReady = false;
        updateCheckoutSubmitState();
        return;
    }

    shippingReady = false;
    updateCheckoutSubmitState();
    summaryShipping.textContent = 'Calculando...';
    summaryTotal.textContent = '—';

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
        shippingReady = true;
        updateCheckoutSubmitState();
    } catch (error) {
        summaryShipping.textContent = '—';
        summaryTotal.textContent = '—';
        shippingError.textContent = error.message;
        shippingError.classList.remove('d-none');
        shippingReady = false;
        updateCheckoutSubmitState();
    }
}

if (createAccountCheckbox) {
    createAccountCheckbox.addEventListener('change', toggleCreateAccountFields);
    toggleCreateAccountFields();
}

regionSelect.addEventListener('change', () => {
    clearPin();
    loadComunas();
});
comunaSelect.addEventListener('change', () => {
    clearPin({ keepMapCenter: true });
    quoteShipping();
    centerMapOnComuna();
});

if (checkoutForm) {
    checkoutForm.addEventListener('input', updateCheckoutSubmitState);
    checkoutForm.addEventListener('change', updateCheckoutSubmitState);
}

initMap();
if (regionSelect.value) loadComunas();
updateCheckoutSubmitState();
</script>
<script src="{{ asset('js/password-toggle.js') }}" defer></script>
@endpush
