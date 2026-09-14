<?php

namespace App\Services\Yandex\Scraper;

class OrganizationIdExtractor
{
    /**
     * Домены Яндекс.Карт, которые считаем валидными.
     */
    private const YANDEX_HOSTS = [
        'yandex.ru', 'yandex.com',
        'yandex.kz', 'yandex.by',
        'yandex.uz', 'yandex.com.tr',
        'yandex.az', 'yandex.co.il',
    ];

    /**
     * Извлекает business_id из любой формы ссылки Яндекс.Карт.
     *
     * Поддерживает:
     *   - /maps/org/<slug>/<id>/
     *   - /maps/org/<id>/
     *   - /maps/<region>/?...poi[uri]=ymapsbm1://org?oid=<id>...
     *   - /maps/?...oid=<id>
     *   - голый ID
     */
    public function extractBusinessId(string $yandexUrl): string
    {
        $url = trim($yandexUrl);

        // 0. Голый ID — самая простая форма
        if (preg_match('~^\d+$~', $url)) {
            return $url;
        }

        // 1. URL должен быть валидным и принадлежать известному хосту
        $host = parse_url($url, PHP_URL_HOST);
        if (! $host || ! $this->isYandexHost($host)) {
            throw new \InvalidArgumentException(
                'Ссылка должна вести на Яндекс.Карты (yandex.ru, yandex.com и т.п.).'
            );
        }

        // Нормализуем хост: убираем www., m., yandex. и оставляем домен
        $host = preg_replace('~^(www|m)\.~', '', strtolower($host));

        // 2. /maps/org/<slug>/<id>/  (формат A)
        if (preg_match('~/maps/org/[^/]+/(\d+)(?:/|$|\?)~', $url, $m)) {
            return $m[1];
        }

        // 3. /maps/org/<id>/  (формат C)
        if (preg_match('~/maps/org/(\d+)(?:/|$|\?)~', $url, $m)) {
            return $m[1];
        }

        // 4. poi[uri]=ymapsbm1://org?oid=<id>  (формат B)
        //    Значение может быть URL-энкодированным (%3A%2F%2F) или сырым.
        $decoded = urldecode($url);
        if (preg_match('~oid=(\d+)~', $decoded, $m)) {
            return $m[1];
        }

        // 5. ?oid=<id>  (формат F)
        $query = parse_url($url, PHP_URL_QUERY);
        if ($query) {
            parse_str($query, $params);
            if (! empty($params['oid']) && ctype_digit((string) $params['oid'])) {
                return (string) $params['oid'];
            }
            if (! empty($params['poi']['uri'])
                && preg_match('~oid=(\d+)~', $params['poi']['uri'], $m)) {
                return $m[1];
            }
        }

        throw new \InvalidArgumentException(
            'Не удалось извлечь ID организации из ссылки. '
            . 'Поддерживаются: permalink /maps/org/.../<id>/, ссылки с poi[uri] и ?oid=<id>.'
        );
    }

    private function isYandexHost(string $host): bool
    {
        $host = strtolower($host);

        foreach (self::YANDEX_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }

        return false;
    }
}