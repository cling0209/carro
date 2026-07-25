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
                    <div id="checkout-address-guide" class="checkout-guide alert alert-warning border-warning mb-3" role="status" aria-live="polite">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-lightbulb-fill checkout-guide__icon mt-1" aria-hidden="true"></i>
                            <div>
                                <div class="fw-bold" id="checkout-guide-title">Paso 1: elige la región</div>
                                <div class="small mb-0" id="checkout-guide-text">Empieza seleccionando tu región para continuar.</div>
                            </div>
                        </div>
                    </div>
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
                        <div class="col-md-6" id="region-field-wrap">
                            <label class="form-label" for="region">1. Región *</label>
                            <select name="region" id="region" class="form-select @error('region') is-invalid @enderror" required>
                                <option value="">Selecciona región</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region['region'] }}" @selected($defaults['region'] === $region['region'])>{{ $region['region'] }}</option>
                                @endforeach
                            </select>
                            @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" id="comuna-field-wrap">
                            <label class="form-label" for="comuna">2. Comuna *</label>
                            <select name="comuna" id="comuna" class="form-select @error('comuna') is-invalid @enderror" required disabled>
                                <option value="">Primero selecciona región</option>
                            </select>
                            @error('comuna')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12" id="map-field-wrap">
                            <label class="form-label">3. Ubicación en el mapa *</label>
                            <p class="form-text mt-0 mb-2">Marca el punto exacto donde quieres recibir el pedido. Sin pin no se puede pagar.</p>
                            <div class="checkout-map-shell">
                                <div id="checkout-map" class="checkout-map" role="application" aria-label="Mapa de ubicación de envío">
                                    <div class="checkout-map-placeholder">Cargando mapa…</div>
                                </div>
                                <div id="checkout-map-lock" class="checkout-map-lock">
                                    <div class="checkout-map-lock__card">
                                        <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                                        <strong id="checkout-map-lock-title">Primero elige región y comuna</strong>
                                        <span id="checkout-map-lock-text">Así el mapa se centra en tu zona.</span>
                                    </div>
                                </div>
                            </div>
                            <div id="checkout-map-status" class="checkout-map-status small mt-2">
                                Selecciona región y comuna para habilitar el mapa.
                            </div>
                            @error('latitude')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <input type="hidden" name="latitude" id="latitude" value="{{ $defaults['latitude'] }}" required>
                            <input type="hidden" name="longitude" id="longitude" value="{{ $defaults['longitude'] }}" required>
                        </div>
                        <div class="col-md-8" id="street-field-wrap">
                            <label class="form-label" for="street">4. Calle *</label>
                            <input type="text" name="street" id="street" class="form-control @error('street') is-invalid @enderror"
                                   value="{{ $defaults['street'] }}" required>
                            @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="street_number">Número</label>
                            <input type="text" name="street_number" id="street_number" class="form-control" value="{{ $defaults['street_number'] }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Depto</label>
                            <input type="text" name="apartment" class="form-control" value="{{ $defaults['apartment'] }}">
                        </div>
                        <div class="col-12">
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <button type="button" id="btn-search-address" class="btn btn-outline-primary btn-sm" disabled>
                                    <i class="bi bi-search"></i> Buscar en el mapa
                                </button>
                                <span class="small text-muted">Si editas la calle, búscala para mover el pin.</span>
                            </div>
                            <div id="address-sync-alert" class="alert alert-warning border-warning small mt-2 mb-0 d-none" role="status">
                                La calle no coincide con el pin. Pulsa <strong>Buscar en el mapa</strong> o vuelve a marcar el pin.
                            </div>
                            <input type="hidden" name="address_synced" id="address_synced" value="0">
                            @error('address_synced')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
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
                        El botón de pago se activará cuando completes región/comuna, marques el <strong>pin</strong>, y la <strong>calle coincida</strong> con el mapa.
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
<link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}?v=1.9.4">
@endpush

@push('scripts')
<script src="{{ asset('vendor/leaflet/leaflet.js') }}?v=1.9.4"></script>
<script>
const regions = @json($regions);
const regionSelect = document.getElementById('region');
const comunaSelect = document.getElementById('comuna');
const savedComuna = @json($defaults['comuna']);
const quoteUrl = @json(route('checkout.shipping.quote'));
const geocodeUrl = @json(route('checkout.geocode'));
const reverseGeocodeUrl = @json(route('checkout.reverse-geocode'));
const geocodeAddressUrl = @json(route('checkout.geocode-address'));
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
const addressSyncedInput = document.getElementById('address_synced');
const addressSyncAlert = document.getElementById('address-sync-alert');
const btnSearchAddress = document.getElementById('btn-search-address');

let shippingReady = false;
let mapReady = false;
let map = null;
let marker = null;
let pinIcon = null;
let centeringMap = false;
let syncedAddressKey = '';
let applyingAddressFromPin = false;

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

const guideTitle = document.getElementById('checkout-guide-title');
const guideText = document.getElementById('checkout-guide-text');
const guideBox = document.getElementById('checkout-address-guide');
const mapLock = document.getElementById('checkout-map-lock');
const mapLockTitle = document.getElementById('checkout-map-lock-title');
const mapLockText = document.getElementById('checkout-map-lock-text');
const regionWrap = document.getElementById('region-field-wrap');
const comunaWrap = document.getElementById('comuna-field-wrap');
const mapWrap = document.getElementById('map-field-wrap');
const streetWrap = document.getElementById('street-field-wrap');

function pinIsSet() {
    return String(latInput.value || '').trim() !== '' && String(lngInput.value || '').trim() !== '';
}

function locationReady() {
    return Boolean(regionSelect.value && comunaSelect.value);
}

function addressKey() {
    return [
        regionSelect.value,
        comunaSelect.value,
        streetInput?.value ?? '',
        streetNumberInput?.value ?? '',
    ].map((v) => String(v).trim().toLowerCase()).join('|');
}

function markAddressSynced() {
    syncedAddressKey = addressKey();
    if (addressSyncedInput) addressSyncedInput.value = '1';
    if (addressSyncAlert) addressSyncAlert.classList.add('d-none');
}

function markAddressUnsynced() {
    syncedAddressKey = '';
    if (addressSyncedInput) addressSyncedInput.value = '0';
}

function isAddressSynced() {
    return pinIsSet()
        && syncedAddressKey !== ''
        && syncedAddressKey === addressKey()
        && String(streetInput?.value || '').trim() !== '';
}

function updateSearchButtonState() {
    if (!btnSearchAddress) return;
    const canSearch = locationReady() && String(streetInput?.value || '').trim() !== '';
    btnSearchAddress.disabled = !canSearch;
}

function setGuide(title, text, tone = 'warning') {
    if (guideTitle) guideTitle.textContent = title;
    if (guideText) guideText.textContent = text;
    if (!guideBox) return;

    guideBox.classList.remove('alert-warning', 'alert-info', 'alert-success', 'border-warning', 'border-info', 'border-success');
    if (tone === 'success') {
        guideBox.classList.add('alert-success', 'border-success');
    } else if (tone === 'info') {
        guideBox.classList.add('alert-info', 'border-info');
    } else {
        guideBox.classList.add('alert-warning', 'border-warning');
    }
}

function clearGuidePulse() {
    [regionWrap, comunaWrap, mapWrap, streetWrap].forEach((el) => {
        if (el) el.classList.remove('checkout-step-pulse');
    });
    if (regionSelect) regionSelect.classList.remove('checkout-field-pulse');
    if (comunaSelect) comunaSelect.classList.remove('checkout-field-pulse');
    if (streetInput) streetInput.classList.remove('checkout-field-pulse');
    if (mapWrap) mapWrap.classList.remove('checkout-map-pulse');
}

function pulseStep(wrap, field = null) {
    clearGuidePulse();
    if (wrap) wrap.classList.add('checkout-step-pulse');
    if (field) field.classList.add('checkout-field-pulse');
}

function updateMapLock() {
    if (!mapLock) return;

    const unlocked = locationReady();
    mapLock.classList.toggle('d-none', unlocked);
    mapLock.classList.toggle('is-locked', !unlocked);

    if (!regionSelect.value) {
        if (mapLockTitle) mapLockTitle.textContent = 'Paso 1: elige la región';
        if (mapLockText) mapLockText.textContent = 'Después podrás elegir la comuna y marcar el mapa.';
    } else if (!comunaSelect.value) {
        if (mapLockTitle) mapLockTitle.textContent = 'Paso 2: elige la comuna';
        if (mapLockText) mapLockText.textContent = 'Con la comuna lista se habilita el mapa.';
    }
}

function updateAddressGuide() {
    updateMapLock();

    if (!regionSelect.value) {
        comunaSelect.disabled = true;
        if (!comunaSelect.value) {
            comunaSelect.innerHTML = '<option value="">Primero selecciona región</option>';
        }
        setGuide('Paso 1: elige la región', 'Empieza por la región. Luego te pediremos la comuna.', 'warning');
        pulseStep(regionWrap, regionSelect);
        setMapStatus('Primero selecciona la región.');
        return;
    }

    comunaSelect.disabled = false;

    if (!comunaSelect.value) {
        setGuide('Paso 2: elige la comuna', 'Ya tienes región. Ahora selecciona la comuna para continuar.', 'warning');
        pulseStep(comunaWrap, comunaSelect);
        setMapStatus('Ahora selecciona la comuna.');
        return;
    }

    if (!pinIsSet()) {
        setGuide('Paso 3: marca el pin en el mapa', 'Toca el mapa en el punto exacto de entrega. Sin pin no se puede pagar.', 'info');
        clearGuidePulse();
        if (mapWrap) mapWrap.classList.add('checkout-step-pulse', 'checkout-map-pulse');
        setMapStatus('Toca el mapa para marcar tu ubicación en ' + comunaSelect.value + '.');
        updateSearchButtonState();
        return;
    }

    if (!String(streetInput.value || '').trim()) {
        setGuide('Paso 4: confirma la calle', 'Revisa o completa la calle y el número de la dirección.', 'info');
        pulseStep(streetWrap, streetInput);
        setMapStatus('Ubicación marcada. Completa la calle si falta.');
        updateSearchButtonState();
        return;
    }

    if (!isAddressSynced()) {
        setGuide('La calle no coincide con el pin', 'Pulsa “Buscar en el mapa” para mover el pin, o vuelve a marcar el pin.', 'warning');
        pulseStep(streetWrap, streetInput);
        if (addressSyncAlert) addressSyncAlert.classList.remove('d-none');
        setMapStatus('Calle editada: búscala en el mapa para sincronizar el pin.');
        updateSearchButtonState();
        return;
    }

    setGuide('Listo: dirección y pin coinciden', 'Puedes ajustar el pin o buscar de nuevo si cambias la calle.', 'success');
    clearGuidePulse();
    if (addressSyncAlert) addressSyncAlert.classList.add('d-none');
    setMapStatus('Dirección alineada con el pin. Puedes arrastrar el pin para ajustar.');
    updateSearchButtonState();
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

        if (field.disabled) {
            return false;
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
    const ready = shippingReady && locationReady() && pinIsSet() && isAddressSynced() && requiredFieldsComplete();
    checkoutSubmit.disabled = !ready;

    if (checkoutSubmitHint) {
        checkoutSubmitHint.classList.toggle('d-none', ready);
    }

    updateAddressGuide();
}

function setMapStatus(text, isError = false) {
    if (!mapStatus) return;
    mapStatus.textContent = text;
    mapStatus.classList.toggle('text-danger', isError);
    mapStatus.classList.toggle('text-success', !isError && pinIsSet());
    mapStatus.classList.toggle('text-muted', !isError && !pinIsSet());
    mapStatus.classList.toggle('checkout-map-status--active', !isError && locationReady() && !pinIsSet());
}

function initMap() {
    if (typeof L === 'undefined') {
        setMapStatus('No se pudo cargar el mapa. Recarga la página e inténtalo de nuevo.', true);
        return;
    }

    pinIcon = L.divIcon({
        className: 'checkout-map-pin',
        html: '<span class="checkout-map-pin__marker" aria-hidden="true"></span>',
        iconSize: [28, 40],
        iconAnchor: [14, 38],
        popupAnchor: [0, -36],
    });

    map = L.map('checkout-map', { scrollWheelZoom: false }).setView([-33.4489, -70.6693], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    map.on('click', (event) => {
        if (!locationReady()) {
            updateAddressGuide();
            return;
        }
        setPin(event.latlng.lat, event.latlng.lng, true);
    });

    if (initialLat !== null && initialLng !== null && locationReady()) {
        setPin(initialLat, initialLng, false);
        map.setView([initialLat, initialLng], 16);
        if (String(streetInput?.value || '').trim() !== '') {
            markAddressSynced();
        }
    }

    setTimeout(() => map.invalidateSize(), 200);
    setTimeout(() => map.invalidateSize(), 800);
    mapReady = true;
}

function setPin(lat, lng, reverseFill) {
    if (!locationReady()) {
        updateAddressGuide();
        return;
    }

    const position = [lat, lng];

    if (!marker) {
        marker = L.marker(position, {
            draggable: true,
            icon: pinIcon,
            title: 'Ubicación de entrega',
            alt: 'Pin de entrega',
        }).addTo(map);
        marker.on('dragend', () => {
            const p = marker.getLatLng();
            setPin(p.lat, p.lng, true);
        });
    } else {
        marker.setLatLng(position);
    }

    latInput.value = Number(lat).toFixed(7);
    lngInput.value = Number(lng).toFixed(7);

    if (!reverseFill && String(streetInput?.value || '').trim() !== '') {
        markAddressSynced();
    }

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
    markAddressUnsynced();
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
        setTimeout(() => map.invalidateSize(), 100);
        updateAddressGuide();
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

        applyingAddressFromPin = true;
        if (data.street && streetInput) {
            streetInput.value = data.street;
        }
        if (streetNumberInput && data.street_number) {
            streetNumberInput.value = data.street_number;
        }
        applyingAddressFromPin = false;
        markAddressSynced();
        updateCheckoutSubmitState();
    } catch (error) {
        applyingAddressFromPin = false;
        if (String(streetInput?.value || '').trim() !== '') {
            markAddressSynced();
        } else {
            markAddressUnsynced();
        }
        setMapStatus('Pin marcado. Completa calle y número manualmente si no se detectaron.', true);
        updateAddressGuide();
    }
}

async function searchAddressOnMap() {
    if (!locationReady() || String(streetInput?.value || '').trim() === '') {
        updateAddressGuide();
        return;
    }

    if (btnSearchAddress) {
        btnSearchAddress.disabled = true;
        btnSearchAddress.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Buscando…';
    }

    setMapStatus('Buscando la dirección en el mapa...');

    try {
        const params = new URLSearchParams({
            region: regionSelect.value,
            comuna: comunaSelect.value,
            street: streetInput.value,
            street_number: streetNumberInput?.value || '',
        });
        const response = await fetch(`${geocodeAddressUrl}?${params.toString()}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'No se encontró la dirección.');
        }

        setPin(data.lat, data.lng, false);
        map.setView([data.lat, data.lng], 17);
        markAddressSynced();
        setMapStatus('Pin movido a la dirección escrita.');
        updateCheckoutSubmitState();
    } catch (error) {
        markAddressUnsynced();
        setMapStatus(error.message || 'No se pudo ubicar esa dirección.', true);
        if (addressSyncAlert) addressSyncAlert.classList.remove('d-none');
        updateAddressGuide();
    } finally {
        if (btnSearchAddress) {
            btnSearchAddress.innerHTML = '<i class="bi bi-search"></i> Buscar en el mapa';
            updateSearchButtonState();
        }
    }
}

function loadComunas() {
    const regionName = regionSelect.value;
    const previousComuna = comunaSelect.value || savedComuna;
    comunaSelect.innerHTML = regionName
        ? '<option value="">Selecciona comuna</option>'
        : '<option value="">Primero selecciona región</option>';

    const region = regions.find(r => r.region === regionName);
    if (!region) {
        comunaSelect.disabled = true;
        clearPin();
        quoteShipping();
        updateAddressGuide();
        return;
    }

    comunaSelect.disabled = false;
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

    updateAddressGuide();
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

    if (!comuna) {
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
        const params = new URLSearchParams({ region, comuna });
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
    updateAddressGuide();
});

if (checkoutForm) {
    checkoutForm.addEventListener('input', updateCheckoutSubmitState);
    checkoutForm.addEventListener('change', updateCheckoutSubmitState);
}

function onAddressTextChanged() {
    if (applyingAddressFromPin) {
        updateCheckoutSubmitState();
        return;
    }
    if (pinIsSet()) {
        markAddressUnsynced();
        if (addressSyncAlert) addressSyncAlert.classList.remove('d-none');
    }
    updateCheckoutSubmitState();
}

if (streetInput) streetInput.addEventListener('input', onAddressTextChanged);
if (streetNumberInput) streetNumberInput.addEventListener('input', onAddressTextChanged);
if (btnSearchAddress) btnSearchAddress.addEventListener('click', searchAddressOnMap);

initMap();
if (regionSelect.value) {
    loadComunas();
} else {
    updateAddressGuide();
}
updateCheckoutSubmitState();
</script>
<script src="{{ asset('js/password-toggle.js') }}" defer></script>
@endpush
