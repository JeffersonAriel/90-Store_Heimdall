<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ─── Empresa ──────────────────────────────────────────────────────
            ['key' => 'company.name',     'value' => '90+ Store',             'group' => 'company', 'label' => 'Nome da Empresa'],
            ['key' => 'company.cnpj',     'value' => '',                       'group' => 'company', 'label' => 'CNPJ'],
            ['key' => 'company.ie',       'value' => '',                       'group' => 'company', 'label' => 'Inscrição Estadual'],
            ['key' => 'company.address',  'value' => '',                       'group' => 'company', 'label' => 'Endereço'],
            ['key' => 'company.city',     'value' => 'São Miguel Paulista',    'group' => 'company', 'label' => 'Cidade'],
            ['key' => 'company.state',    'value' => 'SP',                     'group' => 'company', 'label' => 'Estado'],
            ['key' => 'company.zip',      'value' => '',                       'group' => 'company', 'label' => 'CEP'],
            ['key' => 'company.phone',    'value' => '',                       'group' => 'company', 'label' => 'Telefone'],
            ['key' => 'company.email',    'value' => '',                       'group' => 'company', 'label' => 'E-mail'],
            ['key' => 'company.website',  'value' => '',                       'group' => 'company', 'label' => 'Website'],

            // ─── Loja ─────────────────────────────────────────────────────────
            ['key' => 'store.name',              'value' => '90+ Store',       'group' => 'store', 'label' => 'Nome da Loja'],
            ['key' => 'store.tagline',           'value' => 'A Loja do Esporte', 'group' => 'store', 'label' => 'Slogan'],
            ['key' => 'store.currency',          'value' => 'BRL',             'group' => 'store', 'label' => 'Moeda'],
            ['key' => 'store.currency_symbol',   'value' => 'R$',              'group' => 'store', 'label' => 'Símbolo'],
            ['key' => 'store.products_per_page', 'value' => '24',              'group' => 'store', 'label' => 'Produtos por página'],
            ['key' => 'store.min_installments',  'value' => '50',              'group' => 'store', 'label' => 'Valor mínimo para parcelamento'],
            ['key' => 'store.max_installments',  'value' => '12',              'group' => 'store', 'label' => 'Máximo de parcelas'],
            ['key' => 'store.cashback_percent',  'value' => '2',               'group' => 'store', 'label' => 'Porcentagem de Cashback (%)'],

            // ─── SEO ──────────────────────────────────────────────────────────
            ['key' => 'seo.meta_title',    'value' => '90+ Store — A Loja do Esporte', 'group' => 'seo', 'label' => 'Meta Title'],
            ['key' => 'seo.meta_desc',     'value' => 'Camisas, chuteiras, tênis e acessórios esportivos das melhores marcas.', 'group' => 'seo', 'label' => 'Meta Description'],
            ['key' => 'seo.og_image',      'value' => '',                       'group' => 'seo', 'label' => 'Imagem OG'],
            ['key' => 'seo.analytics_id',  'value' => '',                       'group' => 'seo', 'label' => 'Google Analytics ID'],
            ['key' => 'seo.gtm_id',        'value' => '',                       'group' => 'seo', 'label' => 'Google Tag Manager ID'],

            // ─── Feature Flags ────────────────────────────────────────────────
            ['key' => 'features.ai',          'value' => '1', 'group' => 'features', 'type' => 'boolean', 'label' => 'IA Habilitada'],
            ['key' => 'features.bi',          'value' => '1', 'group' => 'features', 'type' => 'boolean', 'label' => 'BI Habilitado'],
            ['key' => 'features.marketplace', 'value' => '0', 'group' => 'features', 'type' => 'boolean', 'label' => 'Marketplace'],
            ['key' => 'features.blog',        'value' => '1', 'group' => 'features', 'type' => 'boolean', 'label' => 'Blog'],
            ['key' => 'features.wishlist',    'value' => '1', 'group' => 'features', 'type' => 'boolean', 'label' => 'Wishlist'],
            ['key' => 'features.cashback',    'value' => '1', 'group' => 'features', 'type' => 'boolean', 'label' => 'Cashback'],
            ['key' => 'features.2fa_required','value' => '1', 'group' => 'features', 'type' => 'boolean', 'label' => '2FA Obrigatório (admins)'],

            // ─── Frete ────────────────────────────────────────────────────────
            ['key' => 'shipping.origin_zip',   'value' => '08210010', 'group' => 'shipping', 'label' => 'CEP de Origem (São Miguel Paulista)'],
            ['key' => 'shipping.free_above',   'value' => '299',      'group' => 'shipping', 'label' => 'Frete grátis acima de (R$)'],
            ['key' => 'shipping.uber_enabled', 'value' => '1',        'group' => 'shipping', 'type' => 'boolean', 'label' => 'Uber Moto habilitado'],
            ['key' => 'shipping.uber_radius',  'value' => '50',       'group' => 'shipping', 'label' => 'Raio Uber Moto (km)'],
            ['key' => 'shipping.metro_enabled','value' => '1',        'group' => 'shipping', 'type' => 'boolean', 'label' => 'Entrega Metrô habilitada'],

            // ─── Sistema ──────────────────────────────────────────────────────
            ['key' => 'system.version',        'value' => '1.0.0',   'group' => 'system', 'label' => 'Versão'],
            ['key' => 'system.installed_at',   'value' => now()->toDateTimeString(), 'group' => 'system', 'label' => 'Data de Instalação'],
            ['key' => 'system.maintenance',    'value' => '0',       'group' => 'system', 'type' => 'boolean', 'label' => 'Modo Manutenção'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                array_merge(['type' => 'string', 'description' => null], $setting)
            );
        }
    }
}
