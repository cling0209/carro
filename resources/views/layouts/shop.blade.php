<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tienda') — {{ config('app.name', 'Carro') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/shop.css') }}" rel="stylesheet">
    <link href="{{ asset('css/page-loader.css') }}" rel="stylesheet">
    @stack('head')
</head>
<body class="shop-body">
<x-page-loader />
<nav class="navbar navbar-expand-lg shop-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('home') }}">
            <i class="bi bi-bag-heart-fill text-primary me-1"></i>{{ config('app.name', 'Carro') }}
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('catalog') }}">Catálogo</a></li>
            </ul>
            <form class="d-flex me-3 flex-grow-1 flex-lg-grow-0" action="{{ route('catalog') }}" method="get" style="max-width: 320px;">
                <input class="form-control form-control-sm rounded-pill" type="search" name="q" placeholder="Buscar productos..." value="{{ request('q') }}">
            </form>
            <a href="{{ route('cart.index') }}" class="btn btn-outline-primary btn-sm rounded-pill position-relative">
                <i class="bi bi-cart3"></i> Carro
                @if(($cartCount ?? 0) > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ $cartCount }}
                    </span>
                @endif
            </a>
        </div>
    </div>
</nav>

@if(session('success'))
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif
@if(session('error'))
    <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

<main>@yield('content')</main>

<footer class="shop-footer mt-5">
    <div class="container py-5">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="fw-bold">{{ config('app.name', 'Carro') }}</h5>
                <p class="text-secondary mb-0">Tienda online Chile — PHP 8.4, Webpay Plus, envío a todo el país.</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-semibold">Enlaces</h6>
                <ul class="list-unstyled mb-0">
                    <li><a href="{{ route('catalog') }}">Catálogo</a></li>
                    <li><a href="{{ route('cart.index') }}">Mi carro</a></li>
                    <li><a href="/api/documentation" target="_blank">API Swagger</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="fw-semibold">Pago seguro</h6>
                <p class="text-secondary small mb-0">Pagos procesados por Transbank Webpay Plus.</p>
            </div>
        </div>
        <hr class="my-4 border-secondary-subtle">
        <p class="text-center text-secondary small mb-0">&copy; {{ date('Y') }} {{ config('app.name', 'Carro') }}</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script src="{{ asset('js/page-loader.js') }}" defer></script>
<script src="{{ asset('js/product-image.js') }}" defer></script>
@stack('scripts')
</body>
</html>
