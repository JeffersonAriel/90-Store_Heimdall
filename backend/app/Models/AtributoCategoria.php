<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AtributoCategoria extends Model
{
    use HasFactory;

    protected $table = 'atributos_categoria';

    protected $fillable = [
        'categoria_id',
        'nome',
        'tipo',
        'obrigatorio',
        'ordem',
    ];

    protected $casts = [
        'obrigatorio' => 'boolean',
        'ordem' => 'integer',
    ];

    /**
     * Relacionamento com a Categoria associada
     */
    public function categoria()
    {
        return $this->belongsTo(CategoriaTipoProduto::class, 'categoria_id');
    }

    /**
     * Relacionamento com as opções de valores disponíveis para este atributo
     */
    public function opcoes()
    {
        return $this->hasMany(OpcaoAtributo::class, 'atributo_id')->orderBy('ordem');
    }
}
