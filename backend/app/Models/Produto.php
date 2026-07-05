<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'produtos';

    protected $fillable = [
        'fornecedor_id',
        'categoria_id',
        'nome',
        'genero',
        'marca',
        'slug',
        'sku_base',
        'descricao',
        'descricao_curta',
        'preco_custo',
        'preco_venda',
        'tem_desconto',
        'preco_desconto',
        'is_retro',
        'retro_year',
        'estoque_critico',
        'ativo',
        'is_destaque',
        'peso_kg',
        'dimensoes_json',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'preco_custo' => 'decimal:2',
        'preco_venda' => 'decimal:2',
        'tem_desconto' => 'boolean',
        'preco_desconto' => 'decimal:2',
        'is_retro' => 'boolean',
        'retro_year' => 'integer',
        'estoque_critico' => 'integer',
        'ativo' => 'boolean',
        'is_destaque' => 'boolean',
        'peso_kg' => 'decimal:3',
        'dimensoes_json' => 'array',
    ];

    /**
     * Relacionamento com o Fornecedor (Obrigatório)
     */
    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    /**
     * Relacionamento com a Categoria
     */
    public function categoria()
    {
        return $this->belongsTo(CategoriaTipoProduto::class, 'categoria_id');
    }

    /**
     * Relacionamento com as Variações do Produto (Tamanho, Cor, Estoque)
     */
    public function variacoes()
    {
        return $this->hasMany(VariacaoProduto::class, 'produto_id');
    }

    /**
     * Relacionamento com as Fotos associadas ao produto
     */
    public function fotos()
    {
        return $this->hasMany(FotoProduto::class, 'produto_id')->orderBy('ordem');
    }

    /**
     * Relacionamento com fotos de capa
     */
    public function fotoCapa()
    {
        return $this->hasOne(FotoProduto::class, 'produto_id')->where('is_capa', true);
    }

    /**
     * Relacionamento com os valores de atributos cadastrados para este produto
     */
    public function atributosValores()
    {
        return $this->hasMany(ProdutoAtributoValor::class, 'produto_id');
    }

    /**
     * Relacionamento com as avaliações dos clientes na loja
     */
    public function avaliacoes()
    {
        return $this->hasMany(AvaliacaoProduto::class, 'produto_id')->where('aprovado', true);
    }
}
