<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvaliacaoFornecedor extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes_fornecedor';

    protected $fillable = [
        'fornecedor_id',
        'funcionario_id',
        'estrelas',
        'comentario',
    ];

    protected $casts = [
        'estrelas' => 'integer',
    ];

    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class, 'fornecedor_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }
}
