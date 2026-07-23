<?php

namespace App\Services;

use App\Models\ApiConfiguracao;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class MailConfigService
{
    /**
     * Aplica dinamicamente as credenciais de SMTP salvas no módulo de APIs (apis_configuracao)
     */
    public static function apply(): bool
    {
        try {
            $api = ApiConfiguracao::where('slug', 'smtp_mail')
                ->where('ativo', true)
                ->first();

            if (!$api || empty($api->credenciais_json)) {
                return false;
            }

            $creds = is_array($api->credenciais_json) 
                ? $api->credenciais_json 
                : json_decode($api->credenciais_json, true);

            if (empty($creds['host'])) {
                return false;
            }

            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $creds['host'] ?? 'smtp.titan.email');
            Config::set('mail.mailers.smtp.port', (int) ($creds['port'] ?? 465));
            Config::set('mail.mailers.smtp.username', $creds['username'] ?? 'noreply@90store.com.br');
            Config::set('mail.mailers.smtp.password', $creds['password'] ?? '');
            
            $encryption = strtolower($creds['encryption'] ?? 'ssl');
            Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);

            if (!empty($creds['from_address'])) {
                Config::set('mail.from.address', $creds['from_address']);
            }
            if (!empty($creds['from_name'])) {
                Config::set('mail.from.name', $creds['from_name']);
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Erro ao aplicar configurações dinâmicas de e-mail (MailConfigService): " . $e->getMessage());
            return false;
        }
    }
}
