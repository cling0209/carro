@extends('layouts.admin')

@section('title', 'Imagen del producto')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h4 mb-0">Actualizar imagen</h1>
            <p class="text-muted small mb-0">Se guarda siempre como <code>{{ $product->sku }}.jpg</code> (otros formatos se convierten a JPG).</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Listado</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success py-2 small">{{ session('success') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card admin-card mb-3">
                <div class="card-body">
                    <dl class="row small mb-0">
                        <dt class="col-sm-3 text-muted">SKU</dt>
                        <dd class="col-sm-9"><code>{{ $product->sku }}</code></dd>
                        <dt class="col-sm-3 text-muted">Producto</dt>
                        <dd class="col-sm-9">{{ $product->name }}</dd>
                        <dt class="col-sm-3 text-muted">Familia</dt>
                        <dd class="col-sm-9">{{ $product->familia ?: '—' }}</dd>
                        <dt class="col-sm-3 text-muted">Archivo</dt>
                        <dd class="col-sm-9"><code>{{ $product->image_filename ?: ($product->sku.'.jpg') }}</code> <span class="text-muted">(fijo)</span></dd>
                    </dl>
                </div>
            </div>

            <div class="card admin-card">
                <div class="card-body">
                    <form method="post" action="{{ route('admin.products.image.update', $product) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">Subir imagen</label>
                            <input type="file" name="imagen" accept="image/jpeg,image/png,image/webp,image/gif"
                                   class="form-control @error('imagen') is-invalid @enderror" required>
                            @error('imagen')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if(empty($storageImagenConfigurado))
                                <div class="form-text text-warning">R2 no configurado: complete credenciales y R2_PUBLIC_URL / PRODUCT_IMAGE_BASE_URL en .env.</div>
                            @else
                                <div class="form-text">
                                    Se guarda en {{ config('products.r2_prefix') }}/{{ $product->familia ?: 'familia' }}/{{ $product->sku }}.jpg
                                    y se ajusta a {{ config('products.image_listing_size') }}&times;{{ config('products.image_listing_size') }} px (fondo blanco).
                                </div>
                            @endif
                        </div>

                        <p class="small mb-0">
                            <code class="admin-image-url">{{ product_image($product) ?: '—' }}</code>
                        </p>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-sm" @disabled(empty($storageImagenConfigurado) || empty($product->familia))>Guardar imagen</button>
                            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary btn-sm">Volver al listado</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card admin-card">
                <div class="card-body text-center">
                    <h2 class="h6 fw-bold">Vista previa</h2>
                    <x-product-image :product="$product" variant="admin-preview" />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
