<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\Yandex\Scraper\YandexDataPersister;
use App\Services\Yandex\Scraper\YandexScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Throwable;

class ParseOrganizationReviewsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries = 2;
    public array $backoff = [15, 30, 45];

    public function __construct(
        public int $organizationId
    ) {}

    public function handle(
        YandexScraper $scraper,
        YandexDataPersister $persister,
    ): void {
        $organization = Organization::findOrFail(
            $this->organizationId
        );

        $organization->update([
            'parsing_status' => 'in_progress',
        ]);

        try {
            $organizationData = $scraper->scrapeOrganization((string)$organization->business_id);

            $persister->persistOrganization(
                $organization,
                $organizationData
            );


            $reviews = $scraper->scrapeAllReviews((string)$organization->business_id);

            $saved = $persister->persistReviews(
                $organization,
                $reviews
            );

            $organization->update([
                'parsing_status' => 'done',
                'last_parsed_at' => now(),
            ]);

            logger()->info(
                'Yandex organization and reviews parsing completed.',
                [
                    'organization_id' => $organization->id,
                    'business_id' => $organization->business_id,
                    'reviews_parsed' => count($reviews),
                    'reviews_saved' => $saved,
                ]
            );
        } catch (Throwable $e) {
            $organization->update([
                'parsing_status' => 'failed',
            ]);

            logger()->error(
                'Yandex organization and reviews parsing failed.',
                [
                    'organization_id' => $organization->id,
                    'business_id' => $organization->business_id,
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }
}