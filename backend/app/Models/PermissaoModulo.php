<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissaoModulo extends Model
{
    use HasFactory;

    protected $table = 'permissoes_modulo';

    protected $fillable = [
        'perfil_id',
        'modulo',
        'acao',
    ];

    /**
     * Relacionamento reverso com o Perfil
     */
    public function perfil()
    {
        return $this->belongsTo(PerfilPermissao::class, 'perfil_id');
    }
}
