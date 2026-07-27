<?php

namespace App\Models;

use App\Notifications\AdminResetPasswordNotification;
use App\Notifications\CustomerResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_ADMIN = 'admin';

    public const ROLE_EJECUTIVO = 'ejecutivo';

    public const ROLE_BODEGA = 'bodega';

    public const ROLE_CUSTOMER = 'customer';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isEjecutivo(): bool
    {
        return $this->role === self::ROLE_EJECUTIVO;
    }

    public function isWarehouse(): bool
    {
        return $this->role === self::ROLE_BODEGA;
    }

    /** Acceso al panel (admin, ejecutivo o bodega). */
    public function canAccessAdminPanel(): bool
    {
        return $this->isAdmin() || $this->isEjecutivo() || $this->isWarehouse();
    }

    /** CRUD completo de productos (alta/edición/baja/import). */
    public function canManageProducts(): bool
    {
        return $this->isAdmin();
    }

    /** Solo actualizar imagen de producto (flujo dedicado). */
    public function canEditProductImage(): bool
    {
        return $this->isEjecutivo();
    }

    /** Ruta de inicio tras login al panel. */
    public function adminHomeRoute(): string
    {
        if ($this->isWarehouse()) {
            return 'admin.warehouse.index';
        }

        return 'admin.products.index';
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrador',
            self::ROLE_EJECUTIVO => 'Ejecutivo',
            self::ROLE_BODEGA => 'Bodega',
            default => 'Cliente',
        };
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        if ($this->canAccessAdminPanel()) {
            if (! config('admin.otp_enabled')) {
                $this->notify(new AdminResetPasswordNotification($token));
            }

            return;
        }

        $this->notify(new CustomerResetPasswordNotification($token));
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
