<?php

namespace App\Services\Yandex\Scraper;

class ScrapingContextFactory
{
    public function create(): ScrapingContext
    {
        return new ScrapingContext(
            userAgent: config('yandex.user_agent'),
        );
    }
}