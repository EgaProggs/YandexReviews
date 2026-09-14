<?php

namespace App\Services\Yandex\Scraper;

use App\Exceptions\Yandex\YandexClientException;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class YandexClient
{
    private const FETCH_URL = 'https://yandex.ru/maps/api/business/fetchReviews';
    private const ORG_URL = 'https://yandex.ru/maps/org/%s/';
    private const WARMUP_URL = 'https://yandex.ru/';

    /**
     * Получает HTML страницы организации.
     *
     * Никакого парсинга здесь нет.
     */
    public function fetchOrganizationPage(
        ScrapingContext $context,
        string $businessId
    ): string {
        $this->warmup($context);

        $url = sprintf(
            self::ORG_URL,
            $businessId
        );

        try {
            $response = Http::withOptions([
                'cookies' => $context->cookies,
            ])
                ->withHeaders($this->defaultHeaders($context))
                ->timeout((int) config('yandex.timeout', 30))
                ->retry(
                    (int) config('yandex.retry_times', 3),
                    (int) config('yandex.retry_sleep', 1000)
                )
                ->get($url);
        } catch (Throwable $e) {
            throw new YandexClientException(
                'Не удалось получить страницу организации Yandex Maps.',
                previous: $e
            );
        }

        if (! $response->successful()) {
            throw new YandexClientException(
                sprintf(
                    'Yandex Maps вернул HTTP %d при загрузке организации.',
                    $response->status()
                )
            );
        }

        return $response->body();
    }

    /**
     * Получает страницу отзывов.
     *
     * Ответ не разбирается.
     * JSON обрабатывает YandexParser.
     */
    public function fetchReviewsPage(
        ScrapingContext $context,
        string $businessId,
        int $page,
        int $pageSize = 50
    ): Response {
        if (! $context->hasSession()) {
            throw new YandexClientException(
                'Yandex session не инициализирована.'
            );
        }

        $params = [
            'ajax' => '1',
            'businessId' => $businessId,
            'csrfToken' => $context->session['csrfToken'],
            'locale' => 'ru_RU',
            'page' => (string) $page,
            'pageSize' => (string) $pageSize,
            'ranking' => 'by_relevance_org',
            'reqId' => $context->session['reqId'],
            'sessionId' => $context->session['sessionId'],
        ];

        $paramsForSignature = $params;

        /*
         * Это часть алгоритма подписи Yandex.
         * Не заменять на обычный urlencode.
         */
        $paramsForSignature['csrfToken'] = str_replace(
            ':',
            '%3A',
            $params['csrfToken']
        );

        $params['s'] = $this->calculateSignature(
            $paramsForSignature
        );



        try {
            return Http::withOptions([
                'cookies' => $context->cookies,
            ])
                ->withHeaders([
                    'User-Agent' => $context->userAgent
                        ?? config('yandex.user_agent'),
                    'Accept' => '*/*',
                    'Referer' => 'https://yandex.ru/maps/',
                    'x-retpath-y' => 'https://yandex.ru/maps/',
                ])
                ->timeout((int) config('yandex.timeout', 30))
                ->retry(
                    (int) config('yandex.retry_times', 3),
                    (int) config('yandex.retry_sleep', 1000)
                )
                ->get(self::FETCH_URL, $params);
        } catch (Throwable $e) {
            throw new YandexClientException(
                sprintf(
                    'Не удалось получить отзывы Yandex Maps. Страница: %d.',
                    $page
                ),
                previous: $e
            );
        }
    }

    /**
     * Первичный запрос для получения cookies.
     */
    private function warmup(ScrapingContext $context): void
    {
        if ($context->cookies->toArray() !== []) {
            return;
        }
    
        try {
            Http::withOptions([
                'cookies' => $context->cookies,
            ])
            ->withHeaders([
                    'User-Agent' => $context->userAgent
                        ?? config('yandex.user_agent'),
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'ru,en;q=0.9',
                ])
                ->timeout((int) config('yandex.timeout', 30))
                ->get(self::WARMUP_URL);
        } catch (Throwable $e) {
            logger()->warning('Yandex Maps warmup failed.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * HTTP-заголовки для страницы организации.
     */
    private function defaultHeaders(
        ScrapingContext $context
    ): array {
        return [
            'User-Agent' => $context->userAgent
                ?? config('yandex.user_agent'),
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'ru,en;q=0.9',
        ];
    }

    /**
     * Алгоритм подписи запроса Yandex.
     */
    private function calculateSignature(array $params): int
    {
        $ordered = [
            'ajax',
            'businessId',
            'csrfToken',
            'locale',
            'page',
            'pageSize',
            'ranking',
            'reqId',
            'sessionId',
        ];

        $query = collect($ordered)
            ->map(
                fn (string $key) => "{$key}={$params[$key]}"
            )
            ->implode('&');

        $hash = 5381;

        for (
            $i = 0,
            $length = strlen($query);
            $i < $length;
            $i++
        ) {
            $hash = (
                33 * $hash ^ ord($query[$i])
            ) & 0xFFFFFFFF;
        }

        return $hash;
    }
}