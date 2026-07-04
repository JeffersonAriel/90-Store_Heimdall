<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ApiConfiguracao extends Model
{
    use HasFactory;

    protected $table = 'apis_configuracao';

    protected $fillable = [
        'slug',
        'nome',
        'tipo',
        'template_campos_json',
        'credenciais_json',
        'webhook_url',
        'ativo',
        'sandbox',
        'fallback_ordem',
    ];

    protected $casts = [
        'template_campos_json' => 'array',
        'ativo' => 'boolean',
        'sandbox' => 'boolean',
        'fallback_ordem' => 'integer',
    ];

    /**
     * Mutator para encriptar as credenciais automaticamente no banco (Segurança/LGPD).
     */
    public function setCredenciaisJsonAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['credenciais_json'] = null;
            return;
        }

        $this->attributes['credenciais_json'] = Crypt::encryptString(
            is_array($value) ? json_encode($value) : $value
        );
    }

    /**
     * Accessor para desencriptar as credenciais ao ler do banco.
     */
    public function getCredenciaisJsonAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null; // Caso ocorra erro na desencriptação (chaves alteradas)
        }
    }
}
