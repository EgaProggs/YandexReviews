<?php

namespace App\Services\Yandex\Scraper;

use App\Exceptions\Yandex\YandexParserException;
use JsonException;

class YandexParser
{
    public function parseSession(string $html): array
    {
        $state = $this->parseStateView($html);

        $stackItem = $state['stack'][0] ?? null;

        if (! is_array($stackItem)) {
            throw new YandexParserException(
                'Не удалось найти данные stack в state-view Yandex Maps.'
            );
        }

        $item = $stackItem['response']['items'][0]
            ?? $stackItem['results']['items'][0]
            ?? null;

        if (! is_array($item)) {
            throw new YandexParserException(
                'Организация не найдена в state-view Yandex Maps.'
            );
        }

        $csrfToken = $state['config']['csrfToken'] ?? null;
        $sessionId = $state['config']['requestId'] ?? null;

        $reqId = $stackItem['response']['requestId']
            ?? $stackItem['results']['requestId']
            ?? null;

        $businessId = $item['id'] ?? null;

        if (! is_string($csrfToken) || $csrfToken === '') {
            throw new YandexParserException(
                'Не удалось получить csrfToken Yandex Maps.'
            );
        }

        if (! is_string($sessionId) || $sessionId === '') {
            throw new YandexParserException(
                'Не удалось получить sessionId Yandex Maps.'
            );
        }

        if (! is_string($reqId) || $reqId === '') {
            throw new YandexParserException(
                'Не удалось получить reqId Yandex Maps.'
            );
        }

        if ($businessId === null || ! is_scalar($businessId)) {
            throw new YandexParserException(
                'Не удалось получить businessId Yandex Maps.'
            );
        }

        return [
            'csrfToken' => $csrfToken,
            'sessionId' => $sessionId,
            'reqId' => $reqId,
            'businessId' => (string) $businessId,
        ];
    }

    public function parseOrganization(string $html): array
    {
        $state = $this->parseStateView($html);

        $stackItem = $state['stack'][0] ?? null;

        if (! is_array($stackItem)) {
            throw new YandexParserException(
                'Не удалось найти данные stack в state-view Yandex Maps.'
            );
        }

        $item = $stackItem['response']['items'][0]
            ?? $stackItem['results']['items'][0]
            ?? null;

        if (! is_array($item)) {
            throw new YandexParserException(
                'Организация не найдена в state-view Yandex Maps.'
            );
        }

        $coordinates = $item['coordinates'] ?? null;
        $ratingData = $item['ratingData'] ?? [];
        $logo = $item['businessImages']['logo'] ?? [];

        return [
            'business_id' => $this->nullableString(
                $item['id'] ?? null
            ),

            'name' => $this->nullableString(
                $item['title'] ?? null
            ),

            'address' => $this->nullableString(
                $item['fullAddress']
                    ?? $item['address']
                    ?? null
            ),

            'latitude' => $this->nullableFloat(
                $coordinates[1] ?? null
            ),

            'longitude' => $this->nullableFloat(
                $coordinates[0] ?? null
            ),

            'rating' => $this->nullableFloat(
                $ratingData['ratingValue'] ?? null
            ),

            'ratings_count' => $this->nullableInt(
                $ratingData['ratingCount'] ?? null
            ),

            'reviews_count' => $this->nullableInt(
                $ratingData['reviewCount'] ?? null
            ),

            'logo_url_template' => $this->nullableString(
                $logo['urlTemplate'] ?? null
            ),
        ];
    }

    /**
     * Разбирает ответ fetchReviews.
     *
     * Возвращает:
     *
     * [
     *     'error' => ?array,
     *     'reviews' => [...],
     *     'params' => [...],
     * ]
     */
    public function parseReviews(array $json): array
    {
        if (isset($json['error'])) {
            return [
                'error' => $json['error'],
                'reviews' => [],
                'params' => [],
            ];
        }

        $data = $json['data'] ?? $json;

        if (! is_array($data)) {
            throw new YandexParserException(
                'Некорректный формат ответа отзывов Yandex Maps.'
            );
        }

        $rawReviews = $data['reviews']
            ?? $data['items']
            ?? null;

        $params = $data['params'] ?? null;


        
        if ($rawReviews === null && $params === null) {
            throw new YandexParserException(
                'В ответе Yandex Maps отсутствуют отзывы и параметры пагинации.'
            );
        }

        if ($rawReviews !== null && ! is_array($rawReviews)) {
            throw new YandexParserException(
                'Некорректный формат списка отзывов Yandex Maps.'
            );
        }

        if ($params !== null && ! is_array($params)) {
            throw new YandexParserException(
                'Некорректный формат параметров пагинации Yandex Maps.'
            );
        }

        return [
            'error' => null,
            'reviews' => array_map(
                fn (array $review) => $this->parseReview($review),
                $rawReviews ?? []
            ),
            'params' => $params ?? [],
        ];
    }

    private function parseReview(array $review): array
    {
        $author = $review['author'] ?? [];
        $businessComment = $review['businessComment'] ?? null;

        return [
            'external_id' => $this->nullableString(
                $review['reviewId'] ?? null
            ),

            'author_name' => $this->nullableString(
                $author['name'] ?? null
            ),

            'author_public_id' => $this->nullableString(
                $author['id']
                    ?? $author['publicId']
                    ?? null
            ),

            'author_avatar_url' => $this->nullableString(
                $author['avatar']['urlTemplate']
                    ?? $author['avatarUrlTemplate']
                    ?? null
            ),

            'author_level' => $this->nullableInt(
                $author['level'] ?? null
            ),

            'text' => $this->nullableString(
                $review['text'] ?? null
            ),

            'text_language' => $this->nullableString(
                $review['textLanguage'] ?? null
            ),

            'rating' => $this->nullableInt(
                $review['rating'] ?? null
            ),

            'external_updated_at' => $this->parseTimestamp(
                $review['updatedTime'] ?? null
            ),

            'business_reply' => $this->nullableString(
                is_array($businessComment)
                    ? ($businessComment['text'] ?? null)
                    : null
            ),

            'business_reply_updated_at' => $this->parseTimestamp(
                is_array($businessComment)
                    ? ($businessComment['updatedTime'] ?? null)
                    : null
            ),

            'media' => $this->parseMedia($review),
        ];
    }

    private function parseMedia(array $review): array
    {
        $media = [];

        foreach ($review['photos'] ?? [] as $photo) {
            if (! is_array($photo)) {
                continue;
            }

            $externalId = $this->nullableString(
                $photo['id'] ?? null
            );

            if ($externalId === null) {
                continue;
            }

            $media[] = [
                'external_id' => $externalId,
                'type' => 'photo',
                'url_template' => $this->nullableString(
                    $photo['urlTemplate'] ?? null
                ),
                'external_created_at' => $this->parseTimestamp(
                    $photo['createdTime'] ?? null
                ),
            ];
        }

        foreach ($review['videos'] ?? [] as $video) {
            if (! is_array($video)) {
                continue;
            }

            $externalId = $this->nullableString(
                $video['id'] ?? null
            );

            if ($externalId === null) {
                continue;
            }

            $media[] = [
                'external_id' => $externalId,
                'type' => 'video',
                'url_template' => $this->nullableString(
                    $video['urlTemplate']
                        ?? $video['videoUrl']
                        ?? null
                ),
                'external_created_at' => $this->parseTimestamp(
                    $video['createdTime'] ?? null
                ),
            ];
        }

        return $media;
    }

    private function parseStateView(string $html): array
    {
        if (! preg_match(
            '/<script type="application\/json" class="state-view">(.*?)<\/script>/s',
            $html,
            $matches
        )) {
            throw new YandexParserException(
                'state-view не найден на странице Yandex Maps.'
            );
        }

        try {
            $state = json_decode(
                $matches[1],
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new YandexParserException(
                'Не удалось разобрать JSON state-view Yandex Maps.',
                previous: $e
            );
        }

        if (! is_array($state)) {
            throw new YandexParserException(
                'state-view Yandex Maps имеет некорректную структуру.'
            );
        }

        return $state;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_scalar($value) && $value !== ''
            ? (string) $value
            : null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        return is_numeric($value)
            ? (float) $value
            : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value)
            ? (int) $value
            : null;
    }

    private function parseTimestamp(mixed $value): mixed
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_numeric($value)) {
        $timestamp = (int) $value;

        if ($timestamp > 10_000_000_000) {
            $timestamp = intdiv($timestamp, 1000);
        }

        return now()->setTimestamp($timestamp);
    }

    return $value;
}
}