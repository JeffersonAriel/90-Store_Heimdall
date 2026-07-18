<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmDocumento extends Model
{
    use SoftDeletes;

    protected $table = 'crm_documentos';

    protected $fillable = [
        'cliente_id', 'lead_id', 'funcionario_id',
        'tipo', 'nome', 'caminho', 'mime_type', 'tamanho_bytes', 'descricao',
    ];

    protected $casts = [
        'tamanho_bytes' => 'integer',
    ];

    public function cliente()    { return $this->belongsTo(Cliente::class,    'cliente_id'); }
    public function lead()       { return $this->belongsTo(CrmLead::class,    'lead_id'); }
    public function funcionario(){ return $this->belongsTo(Funcionario::class, 'funcionario_id'); }

    public function getTamanhoFormatadoAttribute(): string
    {
        $bytes = $this->tamanho_bytes ?? 0;
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->caminho);
    }
}
