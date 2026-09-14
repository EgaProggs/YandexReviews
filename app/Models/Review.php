<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $fillable = [
        'organization_id',
        'external_id',
        'author_name',
        'author_public_id',
        'author_avatar_url',
        'author_level',
        'text',
        'text_language',
        'rating',
        'external_updated_at',
        'business_reply',
        'business_reply_updated_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'external_updated_at' => 'datetime',
        'business_reply_updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ReviewMedia::class);
    }
}
