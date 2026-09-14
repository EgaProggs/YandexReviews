<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'business_id',
        'yandex_url',
        'name',
        'address',
        'logo_url_template',
        'latitude',
        'longitude',
        'rating',
        'ratings_count',
        'reviews_count',
        'parsing_status',
        'last_parsed_at',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'last_parsed_at' => 'datetime',
    ];

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function parsingRuns(): HasMany
    {
        return $this->hasMany(ParsingRun::class);
    }

    

    public function logoUrl(string $size = 'M'): ?string
    {
        if (! $this->logo_url_template) {
            return null;
        }
    
        return str_replace(
            ['{size}', '%s'],
            [$size, $size],
            $this->logo_url_template
        );
    }

    public function logoVariants(): ?array
    {
        if (! $this->logo_url_template) {
            return null;
        }

        return [
            'small'  => $this->logoUrl('S'),
            'medium' => $this->logoUrl('M'),
            'large'  => $this->logoUrl('L'),
            'orig'   => $this->logoUrl('orig'),
        ];
    }
}
