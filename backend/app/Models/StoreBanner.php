<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreBanner extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'image_mobile_path',
        'video_path',
        'link_url',
        'order',
        'is_active',
        'type',
        'aspect_ratio',
        'category_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'effective_image_mobile',
        'responsive_style',
    ];

    /**
     * Retorna a imagem mobile (se houver) ou faz o fallback automático para a imagem principal.
     */
    public function getEffectiveImageMobileAttribute(): string
    {
        return $this->image_mobile_path ?: $this->image_path;
    }

    /**
     * Retorna estilo CSS responsivo garantindo exibição inteira sem cortes no mobile.
     */
    public function getResponsiveStyleAttribute(): array
    {
        return [
            'object_fit'   => 'contain',
            'width'        => '100%',
            'height'       => 'auto',
            'aspect_ratio' => $this->aspect_ratio ? str_replace(':', '/', $this->aspect_ratio) : '16/9',
        ];
    }

    public function category()
    {
        return $this->belongsTo(CategoriaTipoProduto::class, 'category_id');
    }
}
