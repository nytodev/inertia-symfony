<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Ssr;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Dispatches the Inertia page object to the Node.js SSR server via HTTP.
 * POST {ssrUrl}/render → { head: string[], body: string }
 * Returns null on any error so the caller falls back to client-side rendering.
 */
final class HttpSsrGateway implements SsrGatewayInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $ssrUrl,
    ) {
    }

    public function dispatch(array $page): ?SsrResponse
    {
        $url = rtrim($this->ssrUrl, '/').'/render';

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => $page,
            ]);

            /** @var array{head: list<string>, body: string}|null $data */
            $data = $response->toArray();
        } catch (ExceptionInterface|\JsonException $e) {
            return null;
        }

        if (null === $data) {
            return null;
        }

        return new SsrResponse(
            implode("\n", $data['head']),
            $data['body'],
        );
    }
}
