<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Funcionario extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'funcionarios';

    protected $fillable = [
        'perfil_id',
        'nome',
        'email',
        'password',
        'ativo',
        'two_fa_secret',
        'two_fa_ativo',
        'foto',
        'telefone',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_fa_secret',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'two_fa_ativo' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    /**
     * Relacionamento com o perfil de permissões
     */
    public function perfil()
    {
        return $this->belongsTo(PerfilPermissao::class, 'perfil_id');
    }

    /**
     * Verifica se o funcionário é Administrador master
     */
    public function isAdmin(): bool
    {
        return $this->perfil && $this->perfil->is_admin;
    }

    /**
     * Verifica se tem permissão em determinado módulo e ação
     */
    public function hasPermission(string $module, string $action = 'view'): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->perfil && $this->perfil->permissions()
            ->where('modulo', $module)
            ->where('acao', $action)
            ->exists();
    }
}
