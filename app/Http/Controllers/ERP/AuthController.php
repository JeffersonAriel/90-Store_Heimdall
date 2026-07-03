<?php

namespace App\Http\Controllers\ERP;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuthController extends Controller
{
    /**
     * Processa o login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Rate limiting: 5 tentativas por minuto por IP
        $key = 'login-attempt:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            // Log tentativa de invasão
            activity('security')
                ->withProperties([
                    'ip' => $request->ip(),
                    'email' => $request->email,
                    'user_agent' => $request->userAgent(),
                ])
                ->log("Rate limit atingido para {$request->email}");

            return back()->withErrors([
                'email' => "Muitas tentativas. Tente novamente em {$seconds} segundos.",
            ]);
        }

        if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            RateLimiter::hit($key);

            // Log tentativa falha
            activity('security')
                ->withProperties([
                    'ip' => $request->ip(),
                    'email' => $request->email,
                    'user_agent' => $request->userAgent(),
                ])
                ->log("Tentativa de login falha para {$request->email}");

            return back()->withErrors([
                'email' => 'Credenciais inválidas.',
            ]);
        }

        $user = Auth::user();

        // Verifica se está ativo
        if (! $user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Sua conta está desativada.']);
        }

        RateLimiter::clear($key);

        // Atualiza info de login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_user_agent' => $request->userAgent(),
        ]);

        // Log de login
        activity('auth')
            ->causedBy($user)
            ->withProperties([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log('Login realizado com sucesso');

        $request->session()->regenerate();

        // Se tem 2FA habilitado, redireciona para verificação
        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('erp.2fa');
        }

        $request->session()->put('2fa_verified', true);

        return redirect()->route('erp.dashboard');
    }

    /**
     * Exibe e processa o 2FA.
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        // TODO: Verificar código TOTP com biblioteca (ex: pragmarx/google2fa)
        // Por enquanto placeholder
        $valid = true;

        if (! $valid) {
            return back()->withErrors(['code' => 'Código inválido ou expirado.']);
        }

        $request->session()->put('2fa_verified', true);

        return redirect()->route('erp.dashboard');
    }

    /**
     * Faz logout.
     */
    public function logout(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            activity('auth')
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('Logout realizado');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('erp.login');
    }
}
