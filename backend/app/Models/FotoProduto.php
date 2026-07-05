<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FotoProduto extends Model
{
    use HasFactory;

    protected $table = 'fotos_produto';

    protected $fillable = [
        'produto_id',
        'variacao_id',
        'url',
        'cor',
        'ordem',
        'is_capa',
    ];

    protected $casts = [
        'ordem' => 'integer',
        'is_capa' => 'boolean',
    ];

    /**
     * Relacionamento com o Produto
     */
    public function produto()
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    /**
     * Relacionamento com a Variação (opcional)
     */
    public function variacao()
    {
        return $this->belongsTo(VariacaoProduto::class, 'variacao_id');
    }
}
