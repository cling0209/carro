<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AdminWelcomeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public const PASSWORD_MAX_LENGTH = 20;

    public function index(Request $request): View
    {
        $admins = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_EJECUTIVO, User::ROLE_BODEGA])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';

                return $query->where(function ($q) use ($term) {
                    $q->where('name', 'ilike', $term)
                        ->orWhere('email', 'ilike', $term);
                });
            })
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 WHEN role = 'ejecutivo' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('admins'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'passwordMaxLength' => self::PASSWORD_MAX_LENGTH,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_EJECUTIVO, User::ROLE_BODEGA])],
            'password' => $this->passwordRules(),
        ], $this->passwordMessages());

        $existing = User::query()->where('email', $data['email'])->first();

        if ($existing?->canAccessAdminPanel()) {
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->with('error', 'Ya existe un usuario de panel con ese correo.');
        }

        $roleLabel = match ($data['role']) {
            User::ROLE_BODEGA => 'bodega',
            User::ROLE_EJECUTIVO => 'ejecutivo',
            default => 'administrador',
        };

        if ($existing) {
            $existing->update([
                'name' => $data['name'],
                'password' => $data['password'],
                'role' => $data['role'],
            ]);

            return $this->redirectAfterAdminSaved(
                $existing,
                "La cuenta existente fue promovida a {$roleLabel}."
            );
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
        ]);

        $success = match ($data['role']) {
            User::ROLE_BODEGA => 'Usuario de bodega creado correctamente.',
            User::ROLE_EJECUTIVO => 'Usuario ejecutivo creado correctamente.',
            default => 'Administrador creado correctamente.',
        };

        return $this->redirectAfterAdminSaved($user, $success);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if (! $user->canAccessAdminPanel()) {
            abort(404);
        }

        if ($request->user()->id === $user->id) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        if ($user->isAdmin() && User::query()->where('role', User::ROLE_ADMIN)->count() <= 1) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Debe quedar al menos un administrador en el sistema.');
        }

        $wasWarehouse = $user->isWarehouse();
        $wasEjecutivo = $user->isEjecutivo();
        $user->update(['role' => User::ROLE_CUSTOMER]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', match (true) {
                $wasWarehouse => 'El usuario ya no tiene acceso de bodega.',
                $wasEjecutivo => 'El usuario ya no tiene acceso de ejecutivo.',
                default => 'El usuario ya no tiene permisos de administrador.',
            });
    }

    protected function redirectAfterAdminSaved(User $user, string $message): RedirectResponse
    {
        try {
            $user->notify(new AdminWelcomeNotification());
            $message .= ' Se envió un correo de bienvenida.';
        } catch (\Throwable $e) {
            report($e);
            $message .= ' No se pudo enviar el correo de bienvenida; revisa la configuración SMTP.';
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', $message);
    }

    /**
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'confirmed',
            'max:'.self::PASSWORD_MAX_LENGTH,
            Password::min(8)->letters()->numbers(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function passwordMessages(): array
    {
        return [
            'password.required' => 'Ingresa la contraseña.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.max' => 'La contraseña no puede superar '.self::PASSWORD_MAX_LENGTH.' caracteres.',
            'role.required' => 'Selecciona un perfil.',
            'role.in' => 'El perfil seleccionado no es válido.',
        ];
    }
}
