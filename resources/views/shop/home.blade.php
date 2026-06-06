@extends('layouts.shop')

@section('title', 'Inicio')

@section('content')
<section class="container py-4 py-lg-5">
    <div class="hero-section p-4 p-lg-5 mb-5">
        <div class="row align-items-center position-relative" style="z-index:1">
            <div class="col-lg-7">
                <span class="badge bg-light text-primary mb-3">Envío a todo Chile</span>
                <h1 class="display-5 fw-bold mb-3">Compra fácil, paga seguro con Webpay</h1>
                <p class="lead mb-4 opacity-90">Productos seleccionados, carro inteligente y compra en minutos.</p>
                <a href="{{ route('catalog') }}" class="btn btn-light btn-lg rounded-pill px-4 me-2">Ver catálogo</a>
                <a href="{{ route('cart.index') }}" class="btn btn-outline-light btn-lg rounded-pill px-4">Mi carro</a>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-end">
                <i class="bi bi-truck display-1 opacity-50"></i>
            </div>
        </div>
    </div>

    @if($categories->isNotEmpty())
    <div class="mb-5">
        <h2 class="h4 fw-bold mb-3">Categorías</h2>
        <div class="d-flex flex-wrap gap-2">
            @foreach($categories as $cat)
                <a href="{{ route('catalog', ['category' => $cat->slug]) }}" class="category-pill">{{ $cat->name }}</a>
            @endforeach
        </div>
    </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4 fw-bold mb-0">Destacados</h2>
        <a href="{{ route('catalog') }}" class="text-primary text-decoration-none fw-semibold">Ver todos →</a>
    </div>
    <div class="row g-4">
        @forelse($featured as $product)
            <x-product-card :product="$product" />
        @empty
            <div class="col-12"><p class="text-muted">No hay productos destacados aún.</p></div>
        @endforelse
    </div>
</section>
@endsection
