@extends('layouts.admin')

@section('title', 'Productos')

@section('content')
<div class="container-fluid py-3 py-md-4 px-3">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3 mb-md-4">
        <div>
            <h1 class="h4 fw-bold mb-1">Mantenedor de productos</h1>
            <p class="text-muted mb-0 small">Alta, edición y baja del catálogo.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 admin-page-actions">
            <a href="{{ route('admin.products.export') }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-download"></i>
                <span class="d-none d-sm-inline">Descargar productos</span>
                <span class="d-sm-none">Descargar</span>
            </a>
            <a href="{{ route('admin.products.import') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-upload"></i>
                <span class="d-none d-sm-inline">Carga masiva</span>
                <span class="d-sm-none">Carga</span>
            </a>
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> Nuevo producto
            </a>
        </div>
    </div>

    @if(session('import_errors'))
        <div class="alert alert-warning">
            <strong>Errores en la importación:</strong>
            <ul class="mb-0 mt-2">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card admin-card mb-3">
        <div class="card-body py-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small mb-1">Buscar</label>
                    <input type="search" name="q" class="form-control" placeholder="Nombre, SKU, categoría..."
                           value="{{ request('q') }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Categoría</label>
                    <select name="category_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary flex-grow-1 flex-md-grow-0">Filtrar</button>
                    <a href="{{ route('admin.products.index') }}" class="btn btn-link">Limpiar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Móvil: cards --}}
    <div class="card admin-card d-lg-none">
        <div class="card-body py-2 px-3">
            @forelse($products as $product)
                <div class="admin-product-card">
                    <div class="d-flex gap-3">
                        <div class="flex-shrink-0">
                            <x-product-image :product="$product" variant="admin-thumb" />
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-break">{{ $product->name }}</div>
                            <div class="admin-product-card__meta">
                                <code>{{ $product->sku }}</code>
                                · {{ $product->category?->name ?? 'Sin categoría' }}
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mt-1">
                                <span class="fw-semibold">{{ clp($product->price) }}</span>
                                <span class="text-muted small">Stock {{ $product->stock }}</span>
                                @if($product->is_active)
                                    <span class="badge text-bg-success">Activo</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactivo</span>
                                @endif
                                @if($product->is_featured)
                                    <span class="badge text-bg-primary">Destacado</span>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-1 flex-shrink-0">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary" aria-label="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('admin.products.destroy', $product) }}" method="post"
                                  onsubmit="return confirm('¿Eliminar este producto?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-5">No hay productos.</div>
            @endforelse
        </div>
        @if($products->hasPages())
            <div class="card-footer">{{ $products->links() }}</div>
        @endif
    </div>

    {{-- Desktop: tabla --}}
    <div class="card admin-card d-none d-lg-block">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 admin-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Estado</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td style="width:56px">
                                <x-product-image :product="$product" variant="admin-thumb" />
                            </td>
                            <td><code>{{ $product->sku }}</code></td>
                            <td>
                                <div class="fw-semibold">{{ $product->name }}</div>
                                <small class="text-muted">{{ $product->slug }}</small>
                            </td>
                            <td>
                                {{ $product->category?->name ?? '—' }}
                                @if($product->category?->slug)
                                    <small class="text-muted d-block"><code>{{ $product->category->slug }}</code></small>
                                @endif
                            </td>
                            <td>{{ clp($product->price) }}</td>
                            <td>{{ $product->stock }}</td>
                            <td>
                                @if($product->is_active)
                                    <span class="badge text-bg-success">Activo</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactivo</span>
                                @endif
                                @if($product->is_featured)
                                    <span class="badge text-bg-primary">Destacado</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.products.destroy', $product) }}" method="post" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar este producto?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">No hay productos.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="card-footer">{{ $products->links() }}</div>
        @endif
    </div>
</div>
@endsection
