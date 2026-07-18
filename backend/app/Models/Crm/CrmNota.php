<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmNota extends Model
{
    use SoftDeletes;

    protected $table = 'crm_notas';

    protected $fillable = [
        'cliente_id', 'lead_id', 'funcionario_id',
        'titulo', 'conteudo', 'privada',
    ];

    protected $casts = [
        'privada' => 'boolean',
    ];

    public function cliente()    { return $this->belongsTo(Cliente::class,    'cliente_id'); }
    public function lead()       { return $this->belongsTo(CrmLead::class,    'lead_id'); }
    public function funcionario(){ return $this->belongsTo(Funcionario::class, 'funcionario_id'); }
}
