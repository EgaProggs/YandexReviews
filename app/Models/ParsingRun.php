<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParsingRun extends Model
{
    protected $fillable = [
        'organization_id',
        'status',
        'current_page',
        'total_pages',
        'reviews_found',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'current_page' => 'integer',
        'total_pages' => 'integer',
        'reviews_found' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
