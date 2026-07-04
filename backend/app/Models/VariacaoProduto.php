<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariacaoProduto extends Model
{
    use HasFactory;

    protected $table = 'variacoes_produto';

    protected $fillable = [
        'produto_id',
        'sku',
        'tamanho',
        'cor',
        'preco_adicional',
        'tipo_estoque',
        'estoque_quantidade',
        'estoque_reservado',
        'estoque_minimo',
        'estoque_critico',
        'ativo',
    ];

    protected $casts = [
        'preco_adicional' => 'decimal:2',
        'estoque_quantidade' => 'integer',
        'estoque_reservado' => 'integer',
        'estoque_minimo' => 'integer',
        'estoque_critico' => 'integer',
        'ativo' => 'boolean',
    ];

    /**
     * Relacionamento com o Produto pai
     */
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    /**
     * Relacionamento com as fotos vinculadas a esta variação específica
     */
    public function fotos()
    {
        return $this->hasMany(FotoProduto::class, 'variacao_id');
    }

    /**
     * Relacionamento com as movimentações de estoque desta variação
     */
    public function movimentacoesEstoque()
    {
        return $this->hasMany(MovimentacaoEstoque::class, 'variacao_id');
    }

    /**
     * Retorna a quantidade líquida disponível para venda.
     * Se for dropshipping, retorna sempre true ou estoque simulado grande,
     * pois a baixa definitiva do dropshipping ocorre sob demanda com o fornecedor.
     */
    public function getEstoqueDisponivelAttribute(): int
    {
        if ($this->tipo_estoque === 'dropshipping') {
            return 9999; // Sem limite virtual na loja para dropshipping
        }

        return max(0, $this->estoque_quantidade - $this->estoque_reservado);
    }
}
