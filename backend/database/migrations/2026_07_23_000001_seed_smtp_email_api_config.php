<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ApiConfiguracao;

return new class extends Migration
{
    public function up(): void
    {
        ApiConfiguracao::updateOrCreate(
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
                    'host'         => config('mail.mailers.smtp.host', 'smtp.titan.email'),
                    'port'         => config('mail.mailers.smtp.port', '465'),
                    'encryption'   => config('mail.mailers.smtp.encryption', 'ssl'),
                    'username'     => config('mail.mailers.smtp.username', 'noreply@90store.com.br'),
                    'password'     => env('MAIL_PASSWORD', ''),
                    'from_address' => config('mail.from.address', 'noreply@90store.com.br'),
                    'from_name'    => config('mail.from.name', '90 Store'),
                ],
                'webhook_url'     => null,
            ]
        );
    }

    public function down(): void
    {
        ApiConfiguracao::where('slug', 'smtp_mail')->delete();
    }
};
