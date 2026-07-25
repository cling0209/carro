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

    public const ROLE_BODEGA = 'bodega';

    public const ROLE_CUSTOMER = 'customer';

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isWarehouse(): bool
    {
        return $this->role === self::ROLE_BODEGA;
    }

    /** Acceso al panel (admin completo o solo bodega). */
    public function canAccessAdminPanel(): bool
    {
        return $this->isAdmin() || $this->isWarehouse();
    }

    /** Ruta de inicio tras login al panel. */
    public function adminHomeRoute(): string
    {
        return $this->isWarehouse()
            ? 'admin.warehouse.index'
            : 'admin.products.index';
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrador',
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
