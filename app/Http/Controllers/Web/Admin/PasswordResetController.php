<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public const PASSWORD_MAX_LENGTH = 20;

    public function __construct(protected AdminOtpService $adminOtp) {}

    public function create(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if ($user?->isAdmin()) {
            try {
                $this->adminOtp->send(
                    $user,
                    AdminOtpService::PURPOSE_PASSWORD_RESET,
                    'Recuperar contraseña del panel — '.config('app.name', 'Rómulo'),
                    'Para restablecer la contraseña de tu cuenta de administrador, usa este código en el formulario.',
                );

                $request->session()->put('admin_reset_user_id', $user->id);

                return redirect()
                    ->route('admin.password.reset')
                    ->with('success', 'Te enviamos un código de verificación a tu correo. Si no lo ves, revisa también la carpeta de spam o correo no deseado.');
            } catch (\Throwable $e) {
                report($e);

                return back()->with('error', 'No se pudo enviar el código. Revisa la configuración de correo.');
            }
        }

        return back()->with(
            'success',
            'Si el correo corresponde a un administrador, recibirás un código para restablecer la contraseña. Si no lo ves, revisa también la carpeta de spam o correo no deseado.'
        );
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('admin_reset_user_id')) {
            return redirect()
                ->route('admin.password.request')
                ->with('error', 'Primero solicita un código con tu correo de administrador.');
        }

        $user = User::query()->find($request->session()->get('admin_reset_user_id'));

        if (! $user?->isAdmin()) {
            $request->session()->forget('admin_reset_user_id');

            return redirect()->route('admin.password.request');
        }

        return view('admin.auth.reset-password', [
            'email' => $user->email,
            'passwordMaxLength' => self::PASSWORD_MAX_LENGTH,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
            'password' => [
                'required',
                'confirmed',
                'max:'.self::PASSWORD_MAX_LENGTH,
                PasswordRule::min(8)->letters()->numbers(),
            ],
        ]);

        $userId = $request->session()->get('admin_reset_user_id');

        if (! $userId) {
            return redirect()->route('admin.password.request')->with('error', 'La sesión de recuperación expiró.');
        }

        $user = User::query()->find($userId);

        if (! $user?->isAdmin()) {
            $request->session()->forget('admin_reset_user_id');

            return redirect()->route('admin.password.request')->with('error', 'No se pudo restablecer la contraseña.');
        }

        if (! $this->adminOtp->verify($user, AdminOtpService::PURPOSE_PASSWORD_RESET, $data['code'])) {
            return back()->with('error', 'El código no es válido o ya expiró.');
        }

        $user->forceFill([
            'password' => $data['password'],
            'remember_token' => Str::random(60),
        ])->save();

        $request->session()->forget('admin_reset_user_id');

        return redirect()
            ->route('admin.login')
            ->with('success', 'Contraseña actualizada. Inicia sesión con tu nueva clave; te enviaremos un código de verificación.');
    }
}
