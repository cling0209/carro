@extends('layouts.admin')

@section('title', 'Carga masiva tramos de peso')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Carga masiva de tramos por peso</h1>
            <p class="text-muted mb-0">Importa o actualiza tramos de envío por región y comuna (fuera de RM).</p>
        </div>
        <a href="{{ route('admin.shipping.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a envíos
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
                <div class="card-header bg-white fw-semibold">1. Plantilla y datos actuales</div>
                <div class="card-body">
                    <p class="text-muted">
                        Archivo CSV con separador <strong>punto y coma (;)</strong>. UTF-8 o Excel Windows.
                    </p>
                    <dl class="small mb-4">
                        <dt class="fw-semibold">Columnas obligatorias</dt>
                        <dd>
                            <code>region</code>, <code>comuna</code>, <code>etiqueta</code>,
                            <code>peso_min_kg</code>, <code>adicional_clp</code>
                        </dd>
                        <dt class="fw-semibold">Columnas opcionales</dt>
                        <dd>
                            <code>id</code> (para actualizar por ID),
                            <code>peso_max_kg</code> (vacío = sin límite),
                            <code>orden</code>, <code>activo</code> (1/0)
                        </dd>
                        <dt class="fw-semibold">Actualización</dt>
                        <dd class="mb-0">
                            Si no hay <code>id</code>, se busca por región + comuna + etiqueta.
                            Si no existe, se crea el tramo.
                        </dd>
                    </dl>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.shipping.import.template') }}" class="btn btn-outline-primary" data-no-loader>
                            <i class="bi bi-download"></i> Descargar plantilla
                        </a>
                        <a href="{{ route('admin.shipping.export') }}" class="btn btn-outline-success" data-no-loader>
                            <i class="bi bi-file-earmark-spreadsheet"></i> Descargar tramos actuales
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card admin-card h-100">
                <div class="card-header bg-white fw-semibold">2. Subir archivo</div>
                <div class="card-body">
                    <form method="post" action="{{ route('admin.shipping.import.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Archivo CSV *</label>
                            <input type="file" name="file" accept=".csv,text/csv" class="form-control @error('file') is-invalid @enderror" required>
                            @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Hasta 10 MB. Puedes exportar los tramos actuales, editarlos y volver a subirlos.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Importar tramos
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
