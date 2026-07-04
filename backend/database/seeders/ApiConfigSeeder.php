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
                'template_campos_json' => json_encode([]),
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
                'template_campos_json' => json_encode([]),
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
                'template_campos_json' => json_encode([]),
                'credenciais_json' => null,
                'webhook_url'     => null,
            ],

            // ── Gateways de Pagamento ────────────────────────────
            [
                'slug'            => 'mercadopago',
                'nome'            => 'Mercado Pago',
                'tipo'            => 'gateway',
                'fallback_ordem'  => 99,
                'ativo'           => true,
                'sandbox'         => true, // Sandbox até credenciais reais serem inseridas
                'template_campos_json' => json_encode([
                    ['campo' => 'access_token',       'label' => 'Access Token',        'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'public_key',          'label' => 'Public Key',           'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'webhook_secret',      'label' => 'Webhook Secret',       'obrigatorio' => false, 'tipo' => 'password'],
                    ['campo' => 'notificacao_url',     'label' => 'URL de Notificação',   'obrigatorio' => false, 'tipo' => 'text'],
                ]),
                'credenciais_json' => null,
                'webhook_url'     => '/webhooks/mercadopago',
            ],
            [
                'slug'            => 'pagseguro',
                'nome'            => 'PagSeguro / PagBank',
                'tipo'            => 'gateway',
                'fallback_ordem'  => 99,
                'ativo'           => true,
                'sandbox'         => true,
                'template_campos_json' => json_encode([
                    ['campo' => 'token',              'label' => 'Token de API',          'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'email',              'label' => 'E-mail da conta',        'obrigatorio' => true,  'tipo' => 'email'],
                    ['campo' => 'client_id',          'label' => 'Client ID (OAuth)',      'obrigatorio' => false, 'tipo' => 'text'],
                    ['campo' => 'client_secret',      'label' => 'Client Secret (OAuth)',  'obrigatorio' => false, 'tipo' => 'password'],
                ]),
                'credenciais_json' => null,
                'webhook_url'     => '/webhooks/pagseguro',
            ],
            [
                'slug'            => 'stripe',
                'nome'            => 'Stripe',
                'tipo'            => 'gateway',
                'fallback_ordem'  => 99,
                'ativo'           => true,
                'sandbox'         => true,
                'template_campos_json' => json_encode([
                    ['campo' => 'secret_key',         'label' => 'Secret Key',            'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'publishable_key',    'label' => 'Publishable Key',        'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'webhook_secret',     'label' => 'Webhook Signing Secret', 'obrigatorio' => false, 'tipo' => 'password'],
                ]),
                'credenciais_json' => null,
                'webhook_url'     => '/webhooks/stripe',
            ],

            // ── Frete ────────────────────────────────────────────
            [
                'slug'            => 'melhor_envio',
                'nome'            => 'Melhor Envio',
                'tipo'            => 'frete',
                'fallback_ordem'  => 99,
                'ativo'           => true,
                'sandbox'         => true,
                'template_campos_json' => json_encode([
                    ['campo' => 'token',              'label' => 'Token de API',          'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'client_id',          'label' => 'Client ID',              'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'client_secret',      'label' => 'Client Secret',          'obrigatorio' => true,  'tipo' => 'password'],
                ]),
                'credenciais_json' => null,
                'webhook_url'     => null,
            ],
            [
                'slug'            => 'uber_flash',
                'nome'            => 'Uber Flash',
                'tipo'            => 'frete_local',
                'fallback_ordem'  => 99,
                'ativo'           => true,
                'sandbox'         => true,
                'template_campos_json' => json_encode([
                    ['campo' => 'customer_id',        'label' => 'Customer ID',            'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'client_id',          'label' => 'Client ID',              'obrigatorio' => true,  'tipo' => 'text'],
                    ['campo' => 'client_secret',      'label' => 'Client Secret',          'obrigatorio' => true,  'tipo' => 'password'],
                ]),
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
                'template_campos_json' => json_encode([
                    ['campo' => 'api_key',            'label' => 'API Key',                'obrigatorio' => true,  'tipo' => 'password'],
                    ['campo' => 'company_id',         'label' => 'Company ID',             'obrigatorio' => true,  'tipo' => 'text'],
                ]),
                'credenciais_json' => null,
                'webhook_url'     => null,
            ],
        ];

        foreach ($apis as $api) {
            DB::table('apis_configuracao')->updateOrInsert(
                ['slug' => $api['slug']],
                array_merge($api, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
