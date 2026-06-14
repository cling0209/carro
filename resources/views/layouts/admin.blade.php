<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Administración') — {{ config('app.name', 'Rómulo') }}</title>
    <x-favicon />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/shop.css') }}" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <link href="{{ asset('css/page-loader.css') }}?v=export" rel="stylesheet">
    @stack('head')
</head>
<body class="admin-body">
<x-page-loader />
@if(auth()->check() && auth()->user()->isAdmin())
<nav class="navbar navbar-dark admin-navbar">
    <div class="container-fluid">
        <div class="d-flex align-items-center gap-2">
            <x-shop-logo variant="light" :href="route('admin.products.index')" class="py-0" />
            <span class="navbar-brand fw-bold mb-0 text-white opacity-75">· Administración</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('admin.products.index') }}" class="nav-link-admin {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Productos
            </a>
            <a href="{{ route('admin.categories.index') }}" class="nav-link-admin {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i> Categorías
            </a>
            <a href="{{ route('admin.orders.index') }}" class="nav-link-admin {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Ventas
            </a>
            <a href="{{ route('admin.shipping.index') }}" class="nav-link-admin {{ request()->routeIs('admin.shipping.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Envíos
            </a>
            <a href="{{ route('admin.customers.index') }}" class="nav-link-admin {{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                <i class="bi bi-person-lines-fill"></i> <span class="d-none d-xl-inline">Clientes</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="nav-link-admin {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> <span class="d-none d-xl-inline">Admins</span>
            </a>
            <a href="{{ route('home') }}" class="nav-link-admin" target="_blank">
                <i class="bi bi-shop"></i> Tienda
            </a>
            <a href="{{ route('admin.account.password') }}" class="nav-link-admin {{ request()->routeIs('admin.account.*') ? 'active' : '' }}">
                <i class="bi bi-key"></i> <span class="d-none d-lg-inline">Clave</span>
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

<div class="modal fade" id="adminDialogModal" tabindex="-1" aria-labelledby="adminDialogTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content admin-dialog-content">
            <div class="modal-header">
                <div class="d-flex align-items-center gap-2">
                    <i id="adminDialogIcon" class="bi bi-info-circle-fill admin-dialog-icon admin-dialog-icon--info" aria-hidden="true"></i>
                    <h5 class="modal-title mb-0" id="adminDialogTitle">{{ config('app.name', 'Rómulo') }}</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="adminDialogBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="adminDialogBtnCancel">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="adminDialogBtnOk">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/admin-dialog.js') }}?v=1"></script>
<script src="{{ asset('js/page-loader.js') }}?v=export" defer></script>
<script src="{{ asset('js/product-image.js') }}" defer></script>
@stack('scripts')
</body>
</html>
