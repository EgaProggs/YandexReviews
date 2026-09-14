<?php
namespace App\Services\Yandex\Scraper;

use GuzzleHttp\Cookie\CookieJar;

final class ScrapingContext
{
    public readonly CookieJar $cookies;

    public function __construct(
        public array $session = [],
        public readonly ?string $proxy = null,
        public readonly ?string $userAgent = null,
        ?CookieJar $cookies = null,
    ) {
        $this->cookies = $cookies ?? new CookieJar();
    }

    public function hasSession(): bool
    {
        return $this->session !== [];
    }

    public function setSession(array $session): void
    {
        $this->session = $session;
    }
}