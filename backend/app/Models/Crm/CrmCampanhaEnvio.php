<?php

namespace App\Models\Crm;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Model;

class CrmCampanhaEnvio extends Model
{
    protected $table = 'crm_campanha_envios';

    protected $fillable = [
        'campanha_id', 'cliente_id', 'status', 'enviado_em', 'aberto_em', 'erro_msg',
    ];

    protected $casts = [
        'enviado_em' => 'datetime',
        'aberto_em'  => 'datetime',
    ];

    public function campanha() { return $this->belongsTo(CrmCampanha::class, 'campanha_id'); }
    public function cliente()  { return $this->belongsTo(Cliente::class,     'cliente_id'); }
}
