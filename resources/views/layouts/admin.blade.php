<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — {{ config('app.name', 'Carro') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/shop.css') }}" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    @stack('head')
</head>
<body class="admin-body">
@if(auth()->check() && auth()->user()->isAdmin())
<nav class="navbar navbar-dark admin-navbar">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="{{ route('admin.products.index') }}">
            <i class="bi bi-speedometer2 me-1"></i> Admin {{ config('app.name') }}
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.products.index') }}" class="nav-link-admin {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Productos
            </a>
            <a href="{{ route('admin.orders.index') }}" class="nav-link-admin {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Ventas
            </a>
            <a href="{{ route('admin.shipping.index') }}" class="nav-link-admin {{ request()->routeIs('admin.shipping.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Envíos
            </a>
            <a href="{{ route('home') }}" class="nav-link-admin" target="_blank">
                <i class="bi bi-shop"></i> Tienda
            </a>
            <span class="text-white-50 small d-none d-md-inline">{{ auth()->user()->name }}</span>
            <form action="{{ route('admin.logout') }}" method="post" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm">Salir</button>
            </form>
        </div>
    </div>
</nav>
@endif

@if(session('success'))
    <div class="container-fluid mt-3">
        <div class="alert alert-success alert-dismissible fade show mb-0">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif
@if(session('error'))
    <div class="container-fluid mt-3">
        <div class="alert alert-danger alert-dismissible fade show mb-0">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

<main class="admin-main">@yield('content')</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/product-image.js') }}" defer></script>
@stack('scripts')
</body>
</html>
