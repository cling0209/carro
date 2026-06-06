@extends('layouts.admin')

@section('title', 'Carga masiva de productos')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Carga masiva de productos</h1>
            <p class="text-muted mb-0">Descarga la plantilla CSV, complétala y súbela para crear o actualizar productos por SKU.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>

    @if(session('import_errors'))
        <div class="alert alert-warning">
            <strong>Algunas filas no se importaron:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card admin-card h-100">
                <div class="card-header bg-white fw-semibold">1. Descargar plantilla</div>
                <div class="card-body">
                    <p class="text-muted">
                        Archivo CSV con separador <strong>punto y coma (;)</strong>, codificación UTF-8.
                        Incluye una fila de ejemplo.
                    </p>
                    <dl class="small mb-4">
                        <dt class="fw-semibold">Columnas obligatorias</dt>
                        <dd><code>sku</code>, <code>nombre</code>, <code>precio</code>, <code>stock</code></dd>
                        <dt class="fw-semibold">Columnas opcionales</dt>
                        <dd>
                            <code>categoria_slug</code>, <code>slug</code>, <code>descripcion</code>,
                            <code>precio_referencia</code>, <code>peso_kg</code>, <code>activo</code> (1/0),
                            <code>destacado</code> (1/0), <code>familia</code> (carpeta imagen),
                            <code>nombre_archivo</code> (ej. <code>90503_medium.jpg</code>)
                        </dd>
                    </dl>
                    <a href="{{ route('admin.products.import.template') }}" class="btn btn-outline-primary"
                       download="plantilla_productos.csv" data-no-loader>
                        <i class="bi bi-download"></i> Descargar plantilla CSV
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card admin-card h-100">
                <div class="card-header bg-white fw-semibold">2. Subir archivo</div>
                <div class="card-body">
                    <form method="post" action="{{ route('admin.products.import.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Archivo CSV *</label>
                            <input type="file" name="file" accept=".csv,text/csv"
                                   class="form-control @error('file') is-invalid @enderror" required>
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">
                                Máximo 2 MB. Si un SKU ya existe, el producto se actualiza.
                                Si estaba dado de baja, se reactiva.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Importar productos
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
