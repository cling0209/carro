@extends('layouts.admin')

@section('title', $product->exists ? 'Editar producto' : 'Nuevo producto')

@section('content')
@php
    $storageImagenConfigurado = $storageImagenConfigurado ?? false;
    $skuPreview = old('sku', $product->sku) ?: 'SKU';
    $filenamePreview = $product->image_filename ?: ($skuPreview.'.jpg');
@endphp
<div class="container-fluid py-4">
    <div class="mb-4">
        <a href="{{ route('admin.products.index') }}" class="text-decoration-none small">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
        <h1 class="h3 fw-bold mt-2">{{ $product->exists ? 'Editar producto' : 'Nuevo producto' }}</h1>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card admin-card">
                <div class="card-body p-4">
                    <form method="post"
                          action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
                          enctype="multipart/form-data">
                        @csrf
                        @if($product->exists) @method('PUT') @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $product->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                       value="{{ old('slug', $product->slug) }}" placeholder="auto desde nombre">
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SKU (código) *</label>
                                <input type="text" name="sku" id="sku" class="form-control @error('sku') is-invalid @enderror"
                                       value="{{ old('sku', $product->sku) }}" required>
                                @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Categoría</label>
                                <select name="category_id" class="form-select">
                                    <option value="">Sin categoría</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>
                                            {{ $cat->name }} ({{ $cat->slug }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Familia (carpeta imagen)</label>
                                <input type="text" name="familia" class="form-control @error('familia') is-invalid @enderror"
                                       value="{{ old('familia', $product->familia) }}"
                                       placeholder="Ej. LIB">
                                @error('familia')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text">Carpeta en el almacenamiento de imágenes.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nombre de archivo imagen</label>
                                <input type="text" id="image_filename_display" class="form-control" value="{{ $filenamePreview }}" readonly disabled>
                                <div class="form-text">Siempre <code id="image_filename_hint">{{ $skuPreview }}.jpg</code> al subir (no editable).</div>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Subir imagen</label>
                                <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif"
                                       class="form-control @error('imagen') is-invalid @enderror">
                                @error('imagen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @if(! $storageImagenConfigurado)
                                    <div class="form-text text-warning">R2 no configurado: complete credenciales en .env para poder subir.</div>
                                @else
                                    <div class="form-text">JPG/PNG/WebP/GIF → se convierte a JPG cuadrado {{ config('products.image_listing_size') }}px.</div>
                                @endif
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Precio de venta *</label>
                                <input type="number" name="price" min="0" step="1"
                                       class="form-control @error('price') is-invalid @enderror"
                                       value="{{ old('price', $product->price !== null ? clp_amount($product->price) : '') }}" required>
                                <div class="form-text">Lo que paga el cliente en el carrito y al pagar.</div>
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Precio comparación</label>
                                <input type="number" name="compare_at_price" min="0" step="1" class="form-control"
                                       value="{{ old('compare_at_price', $product->compare_at_price !== null ? clp_amount($product->compare_at_price) : '') }}"
                                       placeholder="Opcional">
                                <div class="form-text">
                                    Precio anterior o de referencia. Solo se muestra en la tienda si es
                                    <strong>mayor</strong> que el precio de venta: aparece tachado, badge «Oferta»
                                    y el cliente paga el precio de venta. Si es igual, menor o vacío, no se usa.
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stock *</label>
                                <input type="number" name="stock" min="0" class="form-control @error('stock') is-invalid @enderror"
                                       value="{{ old('stock', $product->stock) }}" required>
                                @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Peso (kg)</label>
                                <input type="number" name="weight_kg" min="0" step="0.001"
                                       class="form-control @error('weight_kg') is-invalid @enderror"
                                       value="{{ old('weight_kg', $product->weight_kg) }}"
                                       placeholder="Vacío = peso por defecto">
                                @error('weight_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="description" rows="4" class="form-control">{{ old('description', $product->description) }}</textarea>
                            </div>
                            <div class="col-12 d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                                           @checked(old('is_active', $product->is_active))>
                                    <label class="form-check-label" for="is_active">Activo en catálogo</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1"
                                           @checked(old('is_featured', $product->is_featured))>
                                    <label class="form-check-label" for="is_featured">Destacado en inicio</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card admin-card">
                <div class="card-body">
                    <h2 class="h6 fw-bold">Vista previa imagen</h2>
                    <p class="small text-muted">Según base URL + familia + <code>{sku}.jpg</code>.</p>
                    @if($product->exists)
                        <x-product-image :product="$product" variant="admin-preview" />
                        <code class="d-block small mt-2 text-break admin-image-url">{{ product_image($product) ?: '— sin URL —' }}</code>
                    @else
                        <p class="small text-muted mb-0">Guarda el producto para ver la URL generada.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var skuInput = document.getElementById('sku');
    var hint = document.getElementById('image_filename_hint');
    var display = document.getElementById('image_filename_display');
    if (!skuInput || !hint) return;

    function syncFilenameHint() {
        var sku = (skuInput.value || 'SKU').trim() || 'SKU';
        var safe = sku.replace(/[^a-zA-Z0-9._-]+/g, '_') || 'SKU';
        var name = safe + '.jpg';
        hint.textContent = name;
        if (display && !display.dataset.locked) {
            display.value = name;
        }
    }

    @if($product->exists && $product->image_filename)
    if (display) display.dataset.locked = '1';
    @endif

    skuInput.addEventListener('input', syncFilenameHint);
})();
</script>
@endpush
