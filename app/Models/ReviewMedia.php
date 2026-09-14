<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewMedia extends Model
{
    protected $table = 'review_media';

    protected $fillable = [
        'review_id',
        'external_id',
        'type',
        'url_template',
        'external_created_at',
    ];

    protected $casts = [
        'external_created_at' => 'datetime',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
