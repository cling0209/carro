@extends('layouts.admin')

@section('title', 'Cambiar contraseña')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card admin-card">
                <div class="card-header bg-white fw-semibold">Cambiar contraseña</div>
                <div class="card-body">
                    <p class="text-muted small mb-4">
                        Mínimo 6 caracteres, con letras y números. Máximo 10 caracteres.
                    </p>

                    <form method="post" action="{{ route('admin.account.password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label" for="current_password">Contraseña actual *</label>
                            <input type="password" name="current_password" id="current_password"
                                   class="form-control @error('current_password') is-invalid @enderror"
                                   required autocomplete="current-password"
                                   maxlength="10" size="10">
                            @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Nueva contraseña *</label>
                            <input type="password" name="password" id="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required autocomplete="new-password"
                                   minlength="6" maxlength="10" size="10">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password_confirmation">Confirmar nueva contraseña *</label>
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   class="form-control" required autocomplete="new-password"
                                   minlength="6" maxlength="10" size="10">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key"></i> Guardar contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
