<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    use HasFactory;

    protected $table = 'insumos';

    protected $fillable = [
        'nome',
        'custo',
        'estoque',
        'categoria_id'
    ];

    protected $casts = [
        'custo' => 'decimal:2',
        'estoque' => 'integer',
        'categoria_id' => 'integer',
    ];

    /**
     * Relacionamento com a Categoria de Produto
     */
    public function categoria()
    {
        return $this->belongsTo(CategoriaTipoProduto::class, 'categoria_id');
    }
}
