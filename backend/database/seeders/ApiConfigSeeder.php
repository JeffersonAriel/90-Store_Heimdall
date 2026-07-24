<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApiConfigSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $apis = [
            // ── CEP (fallback chain) ─────────────────────────────
            [
                'slug'            => 'viacep',
                'nome'            => 'ViaCEP',
                'tipo'            => 'cep',
                'fallback_ordem'  => 1,
                'ativo'           => true,
                'sandbox'         => false,
                'template_campos_json' => [],
                'credenciais_json' => null, // API pública, sem credenciais
                'webhook_url'     => null,
            ],
            [
                'slug'            => 'brasilapi',
                'nome'            => 'BrasilAPI',
                'tipo'            => 'cep',
                'fallback_ordem'  => 2,
                'ativo'           => true,
                'sandbox'         => false,
                'template_campos_json' => [],
                'credenciais_json' => null,
                'webhook_url'     => null,
            ],
            [
                'slug'            => 'apicep',
                'nome'            => 'ApiCEP / OpenCEP',
                'tipo'            => 'cep',
                'fallback_ordem'  => 3,
                'ativo'           => true,
                'sandbox'         => false,
                'template_campos_json' => [],
                'credenciais_json' => null,
                'webhook_url'     => null,
            ],

            // ── Gateways de Pagamento ────────────────────────────
            [
                'slug'            => 'mercadopago',
                'nome'            => 'Mercado Pago',
                'tipo'            => 'gateway',
                'fallback_ordem'  => 99,
                'ativo'           => false,
                'sandbox'         => true, // Sandbox até credenciais reais serem inseridas
                'template_campos_json' => [
                    ['campo' => 'access_token',       'label' => 'Access Token',        'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'public_key',          'label' => 'Public Key',           'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'webhook_secret',      'label' => 'Webhook Secret',       'obrigatorio' => false, 'tipo' => 'password'],
                    ['campo' => 'notificacao_url',     'label' => 'URL de Notificação',   'obrigatorio' => false, 'tipo' => 'text'],
                ],
                'credenciais_json' => null,
                'webhook_url'     => '/webhooks/mercadopago',
            ],
            [
                'slug'            => 'pagseguro',
                'nome'            => 'PagSeguro / PagBank',
                'tipo'            => 'gateway',
                'fallback_ordem'  => 99,
                'ativo'           => false,
                'sandbox'         => true,
                'template_campos_json' => [
                    ['campo' => 'token',              'label' => 'Token de API',          'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'email',              'label' => 'E-mail da conta',        'obrigatorio' => true,  'tipo' => 'email'],
                    ['campo' => 'client_id',          'label' => 'Client ID (OAuth)',      'obrigatorio' => false, 'tipo' => 'text'],
                    ['campo' => 'client_secret',      'label' => 'Client Secret (OAuth)',  'obrigatorio' => false, 'tipo' => 'password'],
                ],
                'credenciais_json' => null,
                'webhook_url'     => '/webhooks/pagseguro',
            ],
            [
                'slug'            => 'stripe',
                'nome'            => 'Stripe',
                'tipo'            => 'gateway',
                'fallback_ordem'  => 99,
                'ativo'           => false,
                'sandbox'         => true,
                'template_campos_json' => [
                    ['campo' => 'secret_key',         'label' => 'Secret Key',            'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'publishable_key',    'label' => 'Publishable Key',        'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'webhook_secret',     'label' => 'Webhook Signing Secret', 'obrigatorio' => false, 'tipo' => 'password'],
                ],
                'credenciais_json' => null,
                'webhook_url'     => '/webhooks/stripe',
            ],
            [
                'slug'            => 'infinitepay',
                'nome'            => 'InfinitePay',
                'tipo'            => 'gateway',
                'fallback_ordem'  => 99,
                'ativo'           => true,
                'sandbox'         => false,
                'template_campos_json' => [
                    ['campo' => 'handle', 'label' => 'InfiniteTag / Handle', 'obrigatorio' => true, 'tipo' => 'text']
                ],
                'credenciais_json' => [
                    'handle' => 'henrique-gabriel-2j3'
                ],
                'webhook_url'     => '/api/payments/infinitepay/webhook',
            ],


            // ── Frete ────────────────────────────────────────────
            [
                'slug'            => 'melhor_envio',
                'nome'            => 'Melhor Envio',
                'tipo'            => 'frete',
                'fallback_ordem'  => 99,
                'ativo'           => false,
                'sandbox'         => true,
                'template_campos_json' => [
                    ['campo' => 'token',              'label' => 'Token de API',          'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'client_id',          'label' => 'Client ID',              'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'client_secret',      'label' => 'Client Secret',          'obrigatorio' => true,  'tipo' => 'password'],
                ],
                'credenciais_json' => null,
                'webhook_url'     => null,
            ],
            [
                'slug'            => 'superfrete',
                'nome'            => 'SuperFrete',
                'tipo'            => 'frete',
                'fallback_ordem'  => 1,
                'ativo'           => true,
                'sandbox'         => false,
                'template_campos_json' => [
                    ['campo' => 'token',              'label' => 'Token de API',          'obrigatorio' => true,  'tipo' => 'password'],
                ],
                'credenciais_json' => [
                    'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpYXQiOjE3ODM4MjEwMDUsInN1YiI6IjdSZEU4N1lDTU9WNkxJN3Y3N09KSk93UnFaTDIifQ.PTTy4dTooL-zFq_YbhBhqX5TCWl-YLSuiRJfQugUIQk'
                ],
                'webhook_url'     => null,
            ],
            [
                'slug'            => 'uber_flash',
                'nome'            => 'Uber Flash',
                'tipo'            => 'frete_local',
                'fallback_ordem'  => 99,
                'ativo'           => true,
                'sandbox'         => true,
                'template_campos_json' => [
                    ['campo' => 'customer_id',        'label' => 'Customer ID',            'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'client_id',          'label' => 'Client ID',              'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'client_secret',      'label' => 'Client Secret',          'obrigatorio' => true,  'tipo' => 'password'],
                ],
                'credenciais_json' => null,
                'webhook_url'     => null,
            ],
            [
                'slug'            => '99entregas',
                'nome'            => '99 Entregas',
                'tipo'            => 'frete_local',
                'fallback_ordem'  => 99,
                'ativo'           => true,
                'sandbox'         => true,
                'template_campos_json' => [
                    ['campo' => 'api_key',            'label' => 'API Key',                'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'company_id',         'label' => 'Company ID',             'obrigatorio' => true,  'tipo' => 'text'],
                ],
                'credenciais_json' => null,
                'webhook_url'     => null,
            ],
            // ── Evolution API (WhatsApp CRM) ──────────────────────
            [
                'slug'            => 'evolution',
                'nome'            => 'Evolution API (WhatsApp)',
                'tipo'            => 'outro',
                'fallback_ordem'  => 99,
                'ativo'           => false,
                'sandbox'         => false,
                'template_campos_json' => [
                    ['campo' => 'base_url',           'label' => 'URL Base da API (Evolution)', 'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'apikey',            'label' => 'API Key Global (Global Token)', 'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'instance_name',      'label' => 'Nome da Instância',           'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'instance_token',     'label' => 'Token da Instância (Opcional)','obrigatorio' => false, 'tipo' => 'password'],
                ],
                'credenciais_json' => null,
                'webhook_url'     => null,
            ],

            // ── Servidor de E-mail (SMTP / Titan Mail / CRM) ────
            [
                'slug'            => 'smtp_mail',
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
                    'host'         => config('mail.mailers.smtp.host', 'smtp.titan.email'),
                    'port'         => config('mail.mailers.smtp.port', '465'),
                    'encryption'   => config('mail.mailers.smtp.encryption', 'ssl'),
                    'username'     => config('mail.mailers.smtp.username', 'noreply@90store.com.br'),
                    'password'     => env('MAIL_PASSWORD', ''),
                    'from_address' => config('mail.from.address', 'noreply@90store.com.br'),
                    'from_name'    => config('mail.from.name', '90 Store'),
                ],
                'webhook_url'     => null,
            ],
        ];

        foreach ($apis as $api) {
            \App\Models\ApiConfiguracao::updateOrCreate(
                ['slug' => $api['slug']],
                $api
            );
        }
    }
}
