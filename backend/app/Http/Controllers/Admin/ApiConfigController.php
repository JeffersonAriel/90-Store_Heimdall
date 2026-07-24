<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConfiguracao;
use App\Models\FreteRegra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class ApiConfigController extends Controller
{
    public function index()
    {
        // Garante que a integração do Servidor de E-mail (SMTP / Titan Mail) sempre exista e esteja limpa no banco
        $smtpConfig = ApiConfiguracao::firstOrCreate(
            ['slug' => 'smtp_mail'],
            [
                'nome'            => 'Servidor de E-mail (SMTP / Titan Mail)',
                'tipo'            => 'outro',
                'fallback_ordem'  => 1,
                'ativo'           => true,
                'sandbox'         => false,
                'template_campos_json' => [
                    ['campo' => 'host',         'label' => 'Host SMTP (ex: smtp.titan.email)', 'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'port',         'label' => 'Porta (ex: 465 ou 587)',          'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'encryption',   'label' => 'Criptografia (ssl, tls ou none)', 'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'username',     'label' => 'Usuário / E-mail de Autenticação', 'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'password',     'label' => 'Senha / Token de App',            'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'from_address', 'label' => 'E-mail Remetente Exibido',         'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'from_name',    'label' => 'Nome do Remetente Exibido',        'obrigatorio' => true,  'tipo' => 'text'],
                ],
                'credenciais_json' => [
                    'host'         => 'smtp.titan.email',
                    'port'         => '465',
                    'encryption'   => 'ssl',
                    'username'     => 'noreply@90store.com.br',
                    'password'     => env('MAIL_PASSWORD', ''),
                    'from_address' => 'noreply@90store.com.br',
                    'from_name'    => '90 Store',
                ],
                'webhook_url'     => null,
            ]
        );

        // Se por algum motivo antigo as credenciais no banco contiverem 127.0.0.1 ou 2525, sobrescreve para Titan
        $credsActual = json_decode($smtpConfig->credenciais_json ?? '{}', true) ?: [];
        if (empty($credsActual['host']) || in_array($credsActual['host'], ['127.0.0.1', 'localhost'])) {
            $credsActual['host'] = 'smtp.titan.email';
            $credsActual['port'] = '465';
            $credsActual['encryption'] = 'ssl';
            $credsActual['username'] = 'noreply@90store.com.br';
            $credsActual['from_address'] = 'noreply@90store.com.br';
            $credsActual['from_name'] = '90 Store';
            $smtpConfig->update(['credenciais_json' => json_encode($credsActual)]);
        }

        $apis = ApiConfiguracao::orderBy('tipo')->orderBy('fallback_ordem')->get();
        $freteRegra = FreteRegra::where('ativo', true)->first();

        // Oculta/limpa apenas senhas e segredos encriptados enviados para o front-end
        $apis->each(function ($api) {
            if ($api->credenciais_json) {
                $creds = is_array($api->credenciais_json) ? $api->credenciais_json : json_decode($api->credenciais_json, true);
                $masked = [];
                if (is_array($creds)) {
                    foreach ($creds as $key => $val) {
                        if (in_array($key, ['password', 'token', 'secret_key', 'access_token', 'client_secret', 'webhook_secret'])) {
                            $masked[$key] = '********';
                        } else {
                            $masked[$key] = $val;
                        }
                    }
                }
                $api->credenciais_json = json_encode($masked);
            }
        });

        return Inertia::render('ApiConfig/Index', [
            'apis' => $apis,
            'freteRegra' => $freteRegra
        ]);
    }

    /**
     * Atualiza credenciais de uma API (Mercado Pago, Melhor Envio, etc.)
     */
    public function update(Request $request, string $slug)
    {
        $api = ApiConfiguracao::where('slug', $slug)->firstOrFail();

        $request->validate([
            'ativo' => 'boolean',
            'sandbox' => 'boolean',
            'credenciais' => 'required|array',
        ]);

        $novasCredenciais = $request->input('credenciais');
        
        // Se as novas credenciais vierem mascaradas (não alteradas pelo admin), mantém as anteriores
        if ($api->credenciais_json) {
            $antigas = json_decode($api->credenciais_json, true);
            foreach ($novasCredenciais as $key => $value) {
                if ($value === '********' && isset($antigas[$key])) {
                    $novasCredenciais[$key] = $antigas[$key];
                }
            }
        }

        $api->update([
            'ativo' => $request->input('ativo', true),
            'sandbox' => $request->input('sandbox', false),
            'credenciais_json' => json_encode($novasCredenciais),
        ]);

        return back()->with('success', "Configurações da API {$api->nome} salvas com sucesso!");
    }

    /**
     * Cadastra um novo Gateway manual ou API
     */
    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:apis_configuracao,slug',
            'tipo' => 'required|in:gateway,cep,frete,email,outro',
            'ativo' => 'boolean',
            'credenciais' => 'required|array',
        ]);

        ApiConfiguracao::create([
            'nome' => $request->nome,
            'slug' => $request->slug,
            'tipo' => $request->tipo,
            'ativo' => $request->input('ativo', true),
            'sandbox' => false,
            'credenciais_json' => $request->credenciais, // mutator handle encryption
        ]);

        return back()->with('success', "Gateway {$request->nome} adicionado com sucesso!");
    }

    /**
     * Exclui um Gateway cadastrado manualmente
     */
    public function destroy(string $slug)
    {
        $api = ApiConfiguracao::where('slug', $slug)->firstOrFail();
        $api->delete();

        return back()->with('success', "Gateway {$api->nome} removido com sucesso!");
    }

    /**
     * Dispara e-mail de teste utilizando as configurações ativas de SMTP em APIs
     */
    public function testEmail(Request $request)
    {
        $request->validate([
            'email_destino' => 'required|email',
            'assunto'       => 'nullable|string',
            'mensagem'      => 'nullable|string',
        ]);

        $emailDestino = $request->input('email_destino');
        $assunto = $request->input('assunto') ?: 'Teste de Envio de E-mail — 90 Store APIs';
        $mensagem = $request->input('mensagem') ?: 'Este é um e-mail de teste disparado via configurações de SMTP em APIs & Integrações.';

        try {
            $html = \App\Services\DirectMailService::renderBlade('emails.crm_email_html', [
                'clienteNome'   => 'Administrador',
                'assuntoTexto'  => $assunto,
                'mensagemTexto' => $mensagem,
                'pedido'        => null,
            ]);

            \App\Services\DirectMailService::sendDirect(
                $emailDestino,
                'Administrador',
                $assunto,
                $html
            );

            return back()->with('success', "E-mail de teste enviado com sucesso para {$emailDestino}!");
        } catch (\Throwable $e) {
            return back()->with('error', "Falha ao enviar e-mail de teste: " . $e->getMessage());
        }
    }
}
