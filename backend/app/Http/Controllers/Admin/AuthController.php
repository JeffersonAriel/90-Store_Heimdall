<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PragmaRX\Google2FALaravel\Support\Authenticator;

class AuthController extends Controller
{
    /**
     * Exibe o formulário de login do Heimdall
     */
    public function showLogin()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return Inertia::render('Auth/Login', [
            'turnstileSiteKey' => env('TURNSTILE_SITE_KEY'),
        ]);
    }

    /**
     * Processa a tentativa de login de um funcionário
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Validação Cloudflare Turnstile
        $secretKey = env('TURNSTILE_SECRET', env('TURNSTILE_SECRET_KEY'));
        if (!empty($secretKey)) {
            $token = $request->input('cf-turnstile-response');
            if (empty($token)) {
                return back()->withErrors([
                    'email' => 'Por favor, complete a verificação de segurança da Cloudflare.',
                ]);
            }

            try {
                $response = \Illuminate\Support\Facades\Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret'   => $secretKey,
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);

                if (!$response->successful() || !$response->json('success')) {
                    return back()->withErrors([
                        'email' => 'Falha na verificação de segurança (Turnstile). Tente novamente.',
                    ]);
                }
            } catch (\Exception $e) {
                // Failsafe se API estiver inacessível
            }
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember', false);

        if (Auth::guard('admin')->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $employee = Auth::guard('admin')->user();

            // Grava Log de Login (Sucesso)
            DB::table('logs_login')->insert([
                'usuario_tipo' => 'funcionario',
                'usuario_id'   => $employee->id,
                'email'        => $employee->email,
                'ip'           => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'sucesso'      => true,
                'acao'         => 'login',
                'created_at'   => now(),
            ]);

            // Atualiza dados de login no model
            $employee->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            // Se o funcionário tiver 2FA ativo, redireciona para a tela de verificação
            if ($employee->two_fa_ativo) {
                $request->session()->put('admin:auth:2fa_pending', $employee->id);
                Auth::guard('admin')->logout();
                return redirect()->route('admin.login.2fa');
            }

            return redirect()->route('admin.dashboard')->with('success', "Bem-vindo de volta, {$employee->nome}!");
        }

        // Grava Log de Login (Falha)
        DB::table('logs_login')->insert([
            'usuario_tipo' => 'funcionario',
            'usuario_id'   => 0,
            'email'        => $request->email,
            'ip'           => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'sucesso'      => false,
            'motivo_falha' => 'Credenciais inválidas',
            'acao'         => 'login',
            'created_at'   => now(),
        ]);

        // Registra tentativa no log de ameaças para proteção brute-force
        $this->registerLoginThreat($request);

        return back()->withErrors([
            'email' => 'As credenciais fornecidas não correspondem aos nossos registros.',
        ]);
    }

    /**
     * Exibe a verificação 2FA
     */
    public function show2fa(Request $request)
    {
        if (!$request->session()->has('admin:auth:2fa_pending')) {
            return redirect()->route('admin.login');
        }

        return Inertia::render('Auth/Verify2FA');
    }

    /**
     * Processa a verificação do token 2FA
     */
    public function verify2fa(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $pendingId = $request->session()->get('admin:auth:2fa_pending');
        if (!$pendingId) {
            return redirect()->route('admin.login');
        }

        $employee = \App\Models\Funcionario::find($pendingId);

        if ($employee) {
            $google2fa = app('pragmarx.google2fa');
            $valid = $google2fa->verifyKey($employee->two_fa_secret, $request->code);

            if ($valid) {
                Auth::guard('admin')->login($employee);
                $request->session()->forget('admin:auth:2fa_pending');
                $request->session()->regenerate();

                // Log de login com 2FA
                DB::table('logs_login')->insert([
                    'usuario_tipo' => 'funcionario',
                    'usuario_id'   => $employee->id,
                    'email'        => $employee->email,
                    'ip'           => $request->ip(),
                    'user_agent'   => $request->userAgent(),
                    'sucesso'      => true,
                    'acao'         => 'login_2fa',
                    'created_at'   => now(),
                ]);

                return redirect()->route('admin.dashboard')->with('success', 'Autenticação 2FA concluída!');
            }
        }

        // Falha no 2FA
        DB::table('logs_login')->insert([
            'usuario_tipo' => 'funcionario',
            'usuario_id'   => $pendingId,
            'email'        => $employee?->email,
            'ip'           => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'sucesso'      => false,
            'motivo_falha' => 'Token 2FA inválido',
            'acao'         => 'login_2fa',
            'created_at'   => now(),
        ]);

        return back()->withErrors([
            'code' => 'Código de autenticação inválido.',
        ]);
    }

    /**
     * Executa o logout do funcionário
     */
    public function logout(Request $request)
    {
        $employee = Auth::guard('admin')->user();

        if ($employee) {
            DB::table('logs_login')->insert([
                'usuario_tipo' => 'funcionario',
                'usuario_id'   => $employee->id,
                'email'        => $employee->email,
                'ip'           => $request->ip(),
                'user_agent'   => $request->userAgent(),
                'sucesso'      => true,
                'acao'         => 'logout',
                'created_at'   => now(),
            ]);
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Registra possíveis ameaças de brute force se houverem muitas falhas do mesmo IP
     */
    private function registerLoginThreat(Request $request)
    {
        $ip = $request->ip();
        $recentFailures = DB::table('logs_login')
            ->where('ip', $ip)
            ->where('sucesso', false)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->count();

        if ($recentFailures >= 5) {
            DB::table('logs_seguranca')->insert([
                'ip'           => $ip,
                'tipo'         => 'brute_force',
                'detalhe'      => "Múltiplas tentativas de login falhas ({$recentFailures}) para o e-mail: {$request->email}",
                'usuario_tipo' => 'anonimo',
                'bloqueado'    => true,
                'rota'         => $request->getRequestUri(),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            // Auto-bloqueio de IP por 1 hora após brute force confirmado
            DB::table('ips_bloqueados')->updateOrInsert(
                ['ip' => $ip],
                [
                    'motivo'       => "Auto-bloqueio: {$recentFailures} tentativas de brute force detectadas em 10 minutos.",
                    'bloqueado_por' => null,
                    'expires_at'   => now()->addHour(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]
            );
        }
    }
}
