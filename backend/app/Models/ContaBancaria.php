<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContaBancaria extends Model
{
    use HasFactory;

    protected $table = 'contas_bancarias';

    protected $fillable = [
        'banco',
        'agencia',
        'conta',
        'tipo',
        'titular',
        'chave_pix',
        'ativa',
    ];

    protected $casts = [
        'ativa' => 'boolean',
    ];

    public function lancamentos()
    {
        return $this->hasMany(LancamentoFinanceiro::class, 'conta_id');
    }
}
