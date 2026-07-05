<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'cpf' => 'encrypted', // AES-256 cast automático nativo do Laravel 11/10
        'data_nascimento' => 'date',
        'ativo' => 'boolean',
        'pontos_saldo' => 'integer',
    ];

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
