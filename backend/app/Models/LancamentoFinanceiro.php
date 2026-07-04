<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LancamentoFinanceiro extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lancamentos_financeiros';

    protected $fillable = [
        'conta_id',
        'pedido_id',
        'fornecedor_id',
        'tipo',
        'categoria',
        'descricao',
        'valor',
        'data_lancamento',
        'data_competencia',
        'conciliado',
        'conciliado_por',
        'conciliado_em',
        'pago_fornecedor',
        'data_pago_fornecedor',
        'forma_pago_fornecedor',
        'created_by',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'data_lancamento' => 'date',
        'data_competencia' => 'date',
        'conciliado' => 'boolean',
        'conciliado_em' => 'datetime',
        'pago_fornecedor' => 'boolean',
        'data_pago_fornecedor' => 'date',
    ];

    public function conta()
    {
        return $this->belongsTo(ContaBancaria::class, 'conta_id');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function funcionarioCriador()
    {
        return $this->belongsTo(Funcionario::class, 'created_by');
    }

    public function funcionarioConciliador()
    {
        return $this->belongsTo(Funcionario::class, 'conciliado_por');
    }
}
