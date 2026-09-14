<?php

namespace App\Services;

use App\Models\Organization;
use App\Services\Yandex\Scraper\YandexScraper;
use App\Services\Yandex\Scraper\OrganizationIdExtractor;
class OrganizationImportService
{
    public function __construct(
        private readonly OrganizationIdExtractor $idExtractor,
        private readonly YandexScraper $scraper,
    ) {}

    public function import(string $yandexUrl): Organization
    {
        $businessId = $this->idExtractor->extractBusinessId($yandexUrl);

        $data = $this->scraper->scrapeOrganization($businessId);

        return Organization::updateOrCreate(
            [
                'business_id' => $businessId,
            ],
            [
                'yandex_url' => $yandexUrl,
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'rating' => $data['rating'] ?? null,
                'ratings_count' => $data['ratings_count'] ?? null,
                'reviews_count' => $data['reviews_count'] ?? null,
                'logo_url_template' => $data['logo_url_template'] ?? null,
                'parsing_status' => 'pending',
            ]
        );
    }
}