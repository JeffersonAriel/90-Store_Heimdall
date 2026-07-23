<?php

namespace App\Services;

use App\Models\ApiConfiguracao;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class MailConfigService
{
    /**
     * Aplica dinamicamente as credenciais de SMTP salvas no módulo de APIs (apis_configuracao)
     * com suporte completo ao HostGator Titan Mail (Porta 465 / SSL / smtps).
     */
    public static function apply(): bool
    {
        try {
            $api = ApiConfiguracao::where('slug', 'smtp_mail')
                ->where('ativo', true)
                ->first();

            $creds = [];
            if ($api && !empty($api->credenciais_json)) {
                $creds = is_array($api->credenciais_json) 
                    ? $api->credenciais_json 
                    : json_decode($api->credenciais_json, true);
            }

            $host = !empty($creds['host']) ? $creds['host'] : env('MAIL_HOST', 'smtp.titan.email');
            $port = !empty($creds['port']) ? (int) $creds['port'] : (int) env('MAIL_PORT', 465);
            $username = !empty($creds['username']) ? $creds['username'] : env('MAIL_USERNAME', 'noreply@90store.com.br');
            $password = !empty($creds['password']) ? $creds['password'] : env('MAIL_PASSWORD', 'Store90Mais1910!');
            $encryption = !empty($creds['encryption']) ? strtolower($creds['encryption']) : env('MAIL_ENCRYPTION', 'ssl');
            $fromAddress = !empty($creds['from_address']) ? $creds['from_address'] : env('MAIL_FROM_ADDRESS', 'noreply@90store.com.br');
            $fromName = !empty($creds['from_name']) ? $creds['from_name'] : env('MAIL_FROM_NAME', '90 Store');

            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.transport', 'smtp');
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.username', $username);
            Config::set('mail.mailers.smtp.password', $password);
            Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
            
            if ($port === 465 || $encryption === 'ssl') {
                Config::set('mail.mailers.smtp.scheme', 'smtps');
            }

            Config::set('mail.from.address', $fromAddress);
            Config::set('mail.from.name', $fromName);

            return true;
        } catch (\Throwable $e) {
            Log::error("Erro ao aplicar configurações dinâmicas de e-mail (MailConfigService): " . $e->getMessage());
            return false;
        }
    }
}
