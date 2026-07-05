<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaTipoProduto extends Model
{
    use HasFactory;

    protected $table = 'categorias_tipo_produto';

    protected $fillable = [
        'parent_id',
        'nome',
        'slug',
        'descricao',
        'icone',
        'banner_path',
        'ordem',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'ordem' => 'integer',
    ];

    /**
     * Relacionamento com a categoria pai (Árvore autodescritiva)
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Relacionamento com subcategorias filhas
     */
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('ordem');
    }

    /**
     * Relacionamento com os atributos específicos desta categoria
     */
    public function atributos()
    {
        return $this->hasMany(AtributoCategoria::class, 'categoria_id')->orderBy('ordem');
    }

    /**
     * Relacionamento com produtos nesta categoria
     */
    public function produtos()
    {
        return $this->hasMany(Produto::class, 'categoria_id');
    }
}
