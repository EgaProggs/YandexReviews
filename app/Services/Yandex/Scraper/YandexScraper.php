<?php

namespace App\Services\Yandex\Scraper;

use App\Exceptions\Yandex\YandexParserException;
use Illuminate\Http\Client\Response;
use LogicException;

class YandexScraper
{
    public ScrapingContext $context;
    private bool $isInit = false;

    function __construct(
        private readonly YandexClient $client,
        private readonly YandexParser $parser,
        private readonly ScrapingContextFactory $contextFactory,)
    { 
        $this->context = $contextFactory->create();
    }




    /**
     * Загружает и разбирает данные организации.
     *
     * Context создаётся снаружи и используется
     * на всём протяжении scraping run.
     */
    public function scrapeOrganization(
        string $businessId
    ): array {


        $html = $this->client->fetchOrganizationPage(
            $this->context,
            $businessId
        );


        return $this->parser->parseOrganization(
            $html
        );
    }


    private function ensureSession(string $businessId){
        if($this->context->hasSession()){
            return;
        }

        $html = $this->client->fetchOrganizationPage(
            $this->context,
            $businessId
        );
        $session = $this->parser->parseSession(
            $html
        );
        $this->context->setSession($session);
    } 


    private function refreshContext(string $businessId){
        $this->context = $this->contextFactory->create();
        $this->ensureSession($businessId);
    }


    /**
     * Загружает и разбирает одну страницу отзывов.
     */
    public function scrapeReviewsPage(
        string $businessId,
        int $page,
        int $pageSize = 50
    ): array {

        $this->ensureSession($businessId);


        $response = $this->client->fetchReviewsPage(
            $this->context,
            $businessId,
            $page,
            $pageSize
        );

        if(isset(json_decode($response->body())->csrfToken)){
            $this->context->session['csrfToken'] = json_decode($response->body())->csrfToken;
            return $this->scrapeReviewsPage($businessId, $page, $pageSize);
        }

        return $this->parseReviewsResponse(
            $response,
            $page
        );
    }

    /**
     * Загружает и разбирает все доступные страницы отзывов.
     *
     * Context должен быть создан вызывающим кодом
     * и использоваться на всём протяжении процесса.
     */
    public function scrapeAllReviews(
        string $businessId,
        int $pageSize = 50
    ): array {
        $allReviews = [];

        $page = 1;
        $totalPages = null;

        $pageLimit = config('yandex.max_pages', 20);
        $done = 0;
        while (++$done <= $pageLimit) {
            $result = $this->scrapeReviewsPage(
                $businessId,
                $page,
                $pageSize
            );
       
            
            /*
             * Yandex может вернуть error.code = 500
             * после последней доступной страницы.
             *
             * В нашем случае это означает конец пагинации.
             */
            if (! empty($result['error'])) {
                if ($this->isEndOfPaginationError(
                    $result['error']
                )) {
                    break;
                }

                throw new YandexParserException(
                    'Yandex Maps вернул ошибку при загрузке страницы отзывов: ' .
                    json_encode(
                        $result['error'],
                        JSON_UNESCAPED_UNICODE
                    )
                );
                
            }

            $reviews = $result['reviews'];
            $params = $result['params'];

            $allReviews = array_merge(
                $allReviews,
                $reviews
            );

            $totalPages = $this->extractTotalPages(
                $params,
                $totalPages
            );

            if (! $this->hasNextPage(
                $page,
                $totalPages,
                $reviews,
                $pageSize
            )) {
                break;
            }

            $page++;

            $this->delay();
        }

        return $allReviews;
    }

    /**
     * Разбирает HTTP response отзывов через Parser.
     *
     * Сам Scraper не занимается разбором JSON.
     */
    private function parseReviewsResponse(
        Response $response,
        int $page
    ): array {
        if (! $response->successful()) {
            throw new YandexParserException(
                sprintf(
                    'Yandex Maps вернул HTTP %d при загрузке отзывов. Страница: %d.',
                    $response->status(),
                    $page
                )
            );
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new YandexParserException(
                sprintf(
                    'Yandex Maps вернул некорректный JSON. Страница: %d.',
                    $page
                )
            );
        }

        return $this->parser->parseReviews(
            $json
        );
    }

    /**
     * Получает totalPages из ответа Yandex.
     */
    private function extractTotalPages(
        array $params,
        ?int $current
    ): ?int {
        if (
            isset($params['totalPages'])
            && is_numeric($params['totalPages'])
        ) {
            return (int) $params['totalPages'];
        }

        return $current;
    }

    /**
     * Определяет, есть ли следующая страница.
     */
    private function hasNextPage(
        int $page,
        ?int $totalPages,
        array $reviews,
        int $pageSize
    ): bool {
        if ($totalPages !== null) {
            return $page < $totalPages;
        }

        return count($reviews) >= $pageSize;
    }

    /**
     * Yandex иногда возвращает error.code = 500
     * при достижении конца доступной пагинации.
     */
    private function isEndOfPaginationError(
        mixed $error
    ): bool {
        if (! is_array($error)) {
            return false;
        }

        return (string) ($error['code'] ?? '') === '500';
    }

    /**
     * Пауза между запросами страниц.
     */
    private function delay(): void
    {
        $microseconds = (int) config(
            'yandex.page_delay',
            1_000_000
        );

        if ($microseconds > 0) {
            usleep($microseconds);
        }
    }
}