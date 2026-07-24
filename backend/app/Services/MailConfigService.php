<?php

namespace App\Services;

use App\Models\ApiConfiguracao;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

            // Sanitização rígida de IP local legado (127.0.0.1:2525) para HostGator Titan Mail (smtp.titan.email:465)
            $dirty = false;
            if (empty($creds['host']) || in_array(strtolower((string)$creds['host']), ['127.0.0.1', 'localhost', 'ssl://127.0.0.1'])) {
                $creds['host'] = 'smtp.titan.email';
                $dirty = true;
            }
            if (empty($creds['port']) || in_array((string)$creds['port'], ['2525', '25', '1025'])) {
                $creds['port'] = 465;
                $dirty = true;
            }
            if (empty($creds['encryption'])) {
                $creds['encryption'] = 'ssl';
                $dirty = true;
            }
            if (empty($creds['username']) || $creds['username'] === 'null') {
                $creds['username'] = env('MAIL_USERNAME', 'noreply@90store.com.br');
                $dirty = true;
            }
            if (empty($creds['from_address']) || $creds['from_address'] === 'hello@example.com') {
                $creds['from_address'] = 'noreply@90store.com.br';
                $dirty = true;
            }

            if ($api && $dirty) {
                $api->update(['credenciais_json' => $creds]);
            }

            $host        = $creds['host'] ?? env('MAIL_HOST', 'smtp.titan.email');
            $port        = (int) ($creds['port'] ?? env('MAIL_PORT', 465));
            $username    = $creds['username'] ?? env('MAIL_USERNAME', 'noreply@90store.com.br');
            $password    = (!empty($creds['password']) && $creds['password'] !== '********') ? $creds['password'] : env('MAIL_PASSWORD');
            $encryption  = !empty($creds['encryption']) ? strtolower($creds['encryption']) : env('MAIL_ENCRYPTION', 'ssl');
            $fromAddress = (!empty($creds['from_address']) && $creds['from_address'] !== 'hello@example.com') ? $creds['from_address'] : env('MAIL_FROM_ADDRESS', 'noreply@90store.com.br');
            $fromName    = !empty($creds['from_name']) ? $creds['from_name'] : env('MAIL_FROM_NAME', '90 Store');

            // Garante sanitização final na memória
            if (in_array(strtolower((string)$host), ['127.0.0.1', 'localhost', 'ssl://127.0.0.1'])) {
                $host = 'smtp.titan.email';
            }
            if (in_array((string)$port, ['2525', '25', '1025'])) {
                $port = 465;
            }
            if (empty($fromAddress) || $fromAddress === 'hello@example.com') {
                $fromAddress = 'noreply@90store.com.br';
            }

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

            // Reseta a instância do mailer no container para aplicar novas credenciais imediatamente
            try {
                Mail::purge('smtp');
            } catch (\Throwable $e) {}

            return true;
        } catch (\Throwable $e) {
            Log::error("Erro ao aplicar configurações dinâmicas de e-mail (MailConfigService): " . $e->getMessage());
            return false;
        }
    }
}
