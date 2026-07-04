<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fornecedor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'fornecedores';

    protected $fillable = [
        'tipo_pessoa',
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'cpf',
        'email',
        'telefone',
        'whatsapp',
        'website',
        'cep',
        'logradouro',
        'numero',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'condicao_pagamento',
        'prazo_medio_dias',
        'observacoes',
        'ativo',
        'avaliacao_media',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'avaliacao_media' => 'decimal:2',
        'prazo_medio_dias' => 'integer',
    ];

    /**
     * Relacionamento com produtos vinculados ao fornecedor
     */
    public function produtos()
    {
        return $this->hasMany(Produto::class, 'fornecedor_id');
    }

    /**
     * Relacionamento com avaliações do fornecedor
     */
    public function avaliacoes()
    {
        return $this->hasMany(AvaliacaoFornecedor::class, 'fornecedor_id');
    }

    /**
     * Relacionamento com lançamentos financeiros associados
     */
    public function lancamentosFinanceiros()
    {
        return $this->hasMany(LancamentoFinanceiro::class, 'fornecedor_id');
    }
}
