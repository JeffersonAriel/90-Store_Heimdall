<?php

namespace App\Services;

use App\Models\ApiConfiguracao;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class DirectMailService
{
    /**
     * Envia um e-mail usando Symfony Mailer diretamente com as credenciais do Titan Mail,
     * sem passar pelo sistema de cache do Laravel (MailManager).
     */
    public static function sendDirect(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): void {
        $creds = self::getCredentials();

        $dsn = sprintf(
            'smtps://%s:%s@%s:%d',
            rawurlencode($creds['username']),
            rawurlencode($creds['password']),
            $creds['host'],
            $creds['port']
        );

        $transport = Transport::fromDsn($dsn);
        $mailer    = new SymfonyMailer($transport);

        $email = (new Email())
            ->from(new Address($creds['from_address'], $creds['from_name']))
            ->to(new Address($toEmail, $toName))
            ->subject($subject)
            ->html($htmlBody);

        $mailer->send($email);
    }

    /**
     * Renderiza a view Blade de e-mail para HTML puro para uso no envio direto.
     */
    public static function renderBlade(string $view, array $data = []): string
    {
        return View::make($view, $data)->render();
    }

    /**
     * Lê credenciais do banco (apis_configuracao) com fallback para .env
     */
    public static function getCredentials(): array
    {
        $creds = [];

        try {
            $api = ApiConfiguracao::where('slug', 'smtp_mail')->where('ativo', true)->first();
            if ($api && !empty($api->credenciais_json)) {
                $creds = is_array($api->credenciais_json)
                    ? $api->credenciais_json
                    : json_decode($api->credenciais_json, true);
            }
        } catch (\Throwable $e) {
            Log::warning('DirectMailService: falha ao carregar credenciais do banco: ' . $e->getMessage());
        }

        $host     = (!empty($creds['host']) && !in_array($creds['host'], ['127.0.0.1', 'localhost'])) ? $creds['host'] : env('MAIL_HOST', 'smtp.titan.email');
        $port     = (!empty($creds['port']) && !in_array((string)$creds['port'], ['25', '2525', '1025'])) ? (int)$creds['port'] : (int)env('MAIL_PORT', 465);
        $username = (!empty($creds['username']) && $creds['username'] !== 'null') ? $creds['username'] : env('MAIL_USERNAME', 'noreply@90store.com.br');
        $password = (!empty($creds['password']) && $creds['password'] !== '********') ? $creds['password'] : env('MAIL_PASSWORD', '');
        $from     = (!empty($creds['from_address']) && $creds['from_address'] !== 'hello@example.com') ? $creds['from_address'] : env('MAIL_FROM_ADDRESS', 'noreply@90store.com.br');
        $fromName = !empty($creds['from_name']) ? $creds['from_name'] : env('MAIL_FROM_NAME', '90 Store');

        return compact('host', 'port', 'username', 'password', 'from_address', 'from_name') + [
            'from_address' => $from,
            'from_name'    => $fromName,
        ];
    }
}
