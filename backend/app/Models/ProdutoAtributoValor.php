<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdutoAtributoValor extends Model
{
    use HasFactory;

    protected $table = 'produto_atributo_valor';

    protected $fillable = [
        'produto_id',
        'atributo_id',
        'opcao_id',
        'valor_livre',
    ];

    /**
     * Relacionamento com o Produto
     */
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    /**
     * Relacionamento com o Atributo de categoria associado
     */
    public function atributo()
    {
        return $this->belongsTo(AtributoCategoria::class, 'atributo_id');
    }

    /**
     * Relacionamento com a opção selecionada
     */
    public function opcao()
    {
        return $this->belongsTo(OpcaoAtributo::class, 'opcao_id');
    }
}
