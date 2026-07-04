<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerfilPermissao extends Model
{
    use HasFactory;

    protected $table = 'perfis_permissao';

    protected $fillable = [
        'nome',
        'descricao',
        'is_admin',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
    ];

    /**
     * Relacionamento com funcionários associados a este perfil
     */
    public function employees()
    {
        return $this->hasMany(Funcionario::class, 'perfil_id');
    }

    /**
     * Relacionamento com as regras granulares do perfil
     */
    public function permissions()
    {
        return $this->hasMany(PermissaoModulo::class, 'perfil_id');
    }
}
