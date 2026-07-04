<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimentacaoEstoque extends Model
{
    use HasFactory;

    protected $table = 'movimentacoes_estoque';

    protected $fillable = [
        'variacao_id',
        'pedido_id',
        'funcionario_id',
        'tipo',
        'quantidade',
        'estoque_antes',
        'estoque_depois',
        'motivo',
        'referencia_externa',
    ];

    protected $casts = [
        'quantidade' => 'integer',
        'estoque_antes' => 'integer',
        'estoque_depois' => 'integer',
    ];

    /**
     * Relacionamento com a Variação associada
     */
    public function variacao()
    {
        return $this->belongsTo(VariacaoProduto::class, 'variacao_id');
    }

    /**
     * Relacionamento com o Funcionário que realizou a movimentação (caso manual)
     */
    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }

    /**
     * Relacionamento com o Pedido de venda associado (se houver)
     */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
}
