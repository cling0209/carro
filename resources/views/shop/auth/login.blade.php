@extends('layouts.shop')

@section('title', 'Ingresar')

@section('content')
<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="checkout-card card p-4">
                <h1 class="h4 fw-bold mb-1">Ingresar a tu cuenta</h1>
                <p class="text-muted small mb-4">
                    Tus datos de envío se completarán automáticamente en el checkout.
                </p>

                <form method="post" action="{{ route('account.login.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input type="email" name="email" id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" required autofocus>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Contraseña</label>
                        <input type="password" name="password" id="password"
                               class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1"
                               @checked(old('remember'))>
                        <label class="form-check-label" for="remember">Recordarme</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Ingresar</button>
                </form>

                <p class="small text-muted text-center mt-4 mb-0">
                    ¿Primera compra?
                    <a href="{{ route('checkout.index') }}">Ve al checkout</a> y marca «Crear cuenta».
                </p>
            </div>
        </div>
    </div>
</section>
@endsection
