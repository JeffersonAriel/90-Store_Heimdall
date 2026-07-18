<?php

namespace App\Models\Crm;

use App\Models\Funcionario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmTemplateMensagem extends Model
{
    use SoftDeletes;

    protected $table = 'crm_templates_mensagem';

    protected $fillable = [
        'criado_por', 'nome', 'categoria', 'tipo',
        'assunto', 'conteudo', 'variaveis', 'ativo',
    ];

    protected $casts = [
        'variaveis' => 'array',
        'ativo'     => 'boolean',
    ];

    public function criadoPor()
    {
        return $this->belongsTo(Funcionario::class, 'criado_por');
    }

    public function scopeAtivo($query)
    {
        return $query->where('ativo', true);
    }

    public function renderizar(array $variaveis): string
    {
        $conteudo = $this->conteudo;
        foreach ($variaveis as $chave => $valor) {
            $conteudo = str_replace('{{' . $chave . '}}', $valor, $conteudo);
        }
        return $conteudo;
    }
}
