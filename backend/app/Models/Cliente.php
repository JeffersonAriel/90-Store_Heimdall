<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class Cliente extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'clientes';

    protected $fillable = [
        'nome_completo',
        'nome_social',
        'cpf',
        'data_nascimento',
        'email',
        'password',
        'telefone',
        'whatsapp',
        'ativo',
        'referral_code',
        'referral_by',
        'pontos_saldo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        // 'cpf' => 'encrypted' removido: substituído por accessor/mutator manual
        // para capturar DecryptException quando APP_KEY muda entre ambientes
        'data_nascimento' => 'date',
        'ativo'           => 'boolean',
        'pontos_saldo'    => 'integer',
    ];

    // ── CPF: criptografado manualmente com tratamento de erro ─────────────
    public function getCpfAttribute(?string $value): ?string
    {
        if (empty($value)) return null;
        try {
<<<<<<< HEAD
            return \Illuminate\Support\Facades\Crypt::decryptString($value);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
=======
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
>>>>>>> f6b535866c516d9e21d386f88d687b0c48203148
            // Retorna null se o CPF foi criptografado com outra APP_KEY
            // O cliente precisará atualizar o CPF no perfil
            return null;
        }
    }

    public function setCpfAttribute(?string $value): void
    {
<<<<<<< HEAD
        $this->attributes['cpf'] = $value ? \Illuminate\Support\Facades\Crypt::encryptString($value) : null;
=======
        $this->attributes['cpf'] = $value ? Crypt::encryptString($value) : null;
>>>>>>> f6b535866c516d9e21d386f88d687b0c48203148
    }

    public function enderecos()
    {
        return $this->hasMany(EnderecoCliente::class, 'cliente_id');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'cliente_id');
    }

    public function pontos()
    {
        return $this->hasMany(PontoFidelidade::class, 'cliente_id');
    }

    public function indicador()
    {
        return $this->belongsTo(self::class, 'referral_by');
    }

    public function indicados()
    {
        return $this->hasMany(self::class, 'referral_by');
    }
}
