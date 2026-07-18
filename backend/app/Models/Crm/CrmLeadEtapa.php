<?php

namespace App\Models\Crm;

use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Model;

class CrmLeadEtapa extends Model
{
    protected $table = 'crm_lead_etapas';

    protected $fillable = [
        'lead_id', 'etapa_de_id', 'etapa_para_id', 'funcionario_id',
        'dias_na_etapa', 'observacao',
    ];

    protected $casts = [
        'dias_na_etapa' => 'integer',
    ];

    public function lead()
    {
        return $this->belongsTo(CrmLead::class, 'lead_id');
    }

    public function etapaDe()
    {
        return $this->belongsTo(CrmPipelineEtapa::class, 'etapa_de_id');
    }

    public function etapaPara()
    {
        return $this->belongsTo(CrmPipelineEtapa::class, 'etapa_para_id');
    }

    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'funcionario_id');
    }
}
