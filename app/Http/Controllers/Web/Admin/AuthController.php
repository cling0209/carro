<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(protected AdminOtpService $adminOtp) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return redirect()->route('admin.products.index');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Credenciales inválidas.');
        }

        if (! $user->isAdmin()) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Esta cuenta no tiene permisos de administrador.');
        }

        try {
            $this->adminOtp->send(
                $user,
                AdminOtpService::PURPOSE_LOGIN,
                'Código de acceso al panel — '.config('app.name', 'Rómulo'),
                'Para completar tu inicio de sesión en el panel de administración, ingresa este código.',
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'No se pudo enviar el código de verificación. Revisa la configuración de correo.');
        }

        $request->session()->put('admin_pending_user_id', $user->id);
        $request->session()->put('admin_remember', $request->boolean('remember'));

        return redirect()
            ->route('admin.login.verify')
            ->with('success', 'Te enviamos un código de verificación a '.$user->email.'.');
    }

    public function showVerify(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('admin_pending_user_id')) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.verify-code', [
            'email' => User::query()->find($request->session()->get('admin_pending_user_id'))?->email,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $userId = $request->session()->get('admin_pending_user_id');

        if (! $userId) {
            return redirect()->route('admin.login')->with('error', 'La sesión de verificación expiró. Intenta nuevamente.');
        }

        $user = User::query()->find($userId);

        if (! $user?->isAdmin()) {
            $request->session()->forget(['admin_pending_user_id', 'admin_remember']);

            return redirect()->route('admin.login')->with('error', 'La verificación no es válida.');
        }

        if (! $this->adminOtp->verify($user, AdminOtpService::PURPOSE_LOGIN, $data['code'])) {
            return back()->with('error', 'El código no es válido o ya expiró.');
        }

        Auth::login($user, (bool) $request->session()->pull('admin_remember', false));
        $request->session()->forget('admin_pending_user_id');
        $request->session()->regenerate();

        return redirect()->intended(route('admin.products.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Sesión cerrada.');
    }
}
