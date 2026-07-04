<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpcaoAtributo extends Model
{
    use HasFactory;

    protected $table = 'opcoes_atributo';

    protected $fillable = [
        'atributo_id',
        'valor',
        'ordem',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    /**
     * Relacionamento com o Atributo pai
     */
    public function atributo()
    {
        return $this->belongsTo(AtributoCategoria::class, 'atributo_id');
    }
}
