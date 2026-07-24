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

    public function category()
    {
        return $this->belongsTo(CategoriaTipoProduto::class, 'category_id');
    }
}
