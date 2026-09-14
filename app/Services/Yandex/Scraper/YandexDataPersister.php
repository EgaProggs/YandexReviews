<?php

namespace App\Services\Yandex\Scraper;

use App\Models\Organization;
use App\Models\Review;
use App\Models\ReviewMedia;

class YandexDataPersister
{
    public function persistOrganization(
        Organization $organization,
        array $data
    ): void {
        $organization->update([
            'business_id' => $data['business_id'] ?? $organization->business_id,
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'rating' => $data['rating'] ?? null,
            'ratings_count' => $data['ratings_count'] ?? null,
            'reviews_count' => $data['reviews_count'] ?? null,
            'logo_url_template' => $data['logo_url_template'] ?? null,
        ]);
    }

    public function persistReviews(
        Organization $organization,
        array $reviews
    ): int {
        $saved = 0;

        foreach ($reviews as $reviewData) {
            if (! is_array($reviewData)) {
                continue;
            }

            if (empty($reviewData['external_id'])) {
                continue;
            }

            $this->persistReview(
                $organization,
                $reviewData
            );

            $saved++;
        }

        return $saved;
    }

    private function persistReview(
        Organization $organization,
        array $data
    ): void {
        /** @var Review $review */
        $review = Review::updateOrCreate(
            [
                'organization_id' => $organization->id,
                'external_id' => $data['external_id'],
            ],
            [
                'author_name' => $data['author_name'] ?? null,
                'author_public_id' => $data['author_public_id'] ?? null,
                'author_avatar_url' => $data['author_avatar_url'] ?? null,
                'author_level' => $data['author_level'] ?? null,

                'text' => $data['text'] ?? null,
                'text_language' => $data['text_language'] ?? null,
                'rating' => $data['rating'] ?? null,

                'external_updated_at' =>
                    $data['external_updated_at'] ?? null,

                'business_reply' =>
                    $data['business_reply'] ?? null,

                'business_reply_updated_at' =>
                    $data['business_reply_updated_at'] ?? null,
            ]
        );

        $this->persistMedia(
            $review,
            $data['media'] ?? []
        );
    }

    private function persistMedia(
        Review $review,
        array $media
    ): void {
        foreach ($media as $mediaData) {
            if (! is_array($mediaData)) {
                continue;
            }

            if (empty($mediaData['external_id'])) {
                continue;
            }

            ReviewMedia::updateOrCreate(
                [
                    'review_id' => $review->id,
                    'external_id' => $mediaData['external_id'],
                ],
                [
                    'type' =>
                        $mediaData['type'] ?? null,

                    'url_template' =>
                        $mediaData['url_template'] ?? null,

                    'external_created_at' =>
                        $mediaData['external_created_at'] ?? null,
                ]
            );
        }
    }
}