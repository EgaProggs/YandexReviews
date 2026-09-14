<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_id' => $this->external_id,

            'author' => [
                'name' => $this->author_name,
                'public_id' => $this->author_public_id,
                'avatar_url' => $this->author_avatar_url,
                'level' => $this->author_level,
            ],

            'text' => $this->text,
            'text_language' => $this->text_language,
            'rating' => $this->rating,
            'external_updated_at' => $this->external_updated_at,

            'business_reply' => $this->business_reply,
            'business_reply_updated_at' => $this->business_reply_updated_at,

            'media' => ReviewMediaResource::collection($this->whenLoaded('media')),
        ];
    }
}