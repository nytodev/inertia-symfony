<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Ensures the Inertia page object `url` field reflects the public URL
 * as seen by the browser when the app runs behind a reverse proxy that
 * strips a path prefix and forwards it via X-Forwarded-Prefix.
 */
final class ReverseProxyTest extends FunctionalTestCase
{
    private KernelBrowser $client;
    /** @var string[] */
    private array $previousTrustedProxies;
    private int $previousTrustedHeaderSet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        $this->previousTrustedProxies = Request::getTrustedProxies();
        $this->previousTrustedHeaderSet = Request::getTrustedHeaderSet();
    }

    protected function tearDown(): void
    {
        // getTrustedHeaderSet() returns -1 when never set; setTrustedProxies() requires int<0,63>
        Request::setTrustedProxies($this->previousTrustedProxies, min(63, max(0, $this->previousTrustedHeaderSet)));
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Without reverse proxy — regression guard
    // -------------------------------------------------------------------------

    public function testUrlWithoutProxyOnFirstVisit(): void
    {
        $this->client->request('GET', '/test');
        $page = $this->extractPageObjectFromHtml();
        self::assertSame('/test', $page['url']);
    }

    public function testUrlWithoutProxyOnXhrVisit(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $data = $this->decodeJsonResponse();
        self::assertSame('/test', $data['url']);
    }

    public function testUrlWithoutProxyPreservesQueryStringOnXhrVisit(): void
    {
        $this->client->request('GET', '/test?page=3', [], [], ['HTTP_X_INERTIA' => 'true']);
        $data = $this->decodeJsonResponse();
        self::assertSame('/test?page=3', $data['url']);
    }

    public function testUrlWithoutProxyPreservesQueryStringOnFirstVisit(): void
    {
        $this->client->request('GET', '/test?page=3');
        $page = $this->extractPageObjectFromHtml();
        self::assertSame('/test?page=3', $page['url']);
    }

    public function testUrlWithoutProxyPreservesMultipleQueryParams(): void
    {
        // Symfony normalizeQueryString() sorts params alphabetically: filter < page < sort
        $this->client->request('GET', '/test?page=2&sort=desc&filter=active', [], [], ['HTTP_X_INERTIA' => 'true']);
        $data = $this->decodeJsonResponse();
        self::assertSame('/test?filter=active&page=2&sort=desc', $data['url']);
    }

    // -------------------------------------------------------------------------
    // With reverse proxy forwarding X-Forwarded-Prefix
    // -------------------------------------------------------------------------

    public function testUrlIncludesForwardedPrefixOnFirstVisit(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PREFIX);

        $this->client->request('GET', '/test', [], [], [
            'HTTP_X_FORWARDED_PREFIX' => '/app',
        ]);

        $page = $this->extractPageObjectFromHtml();
        self::assertSame('/app/test', $page['url']);
    }

    public function testUrlIncludesForwardedPrefixOnXhrVisit(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PREFIX);

        $this->client->request('GET', '/test', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_FORWARDED_PREFIX' => '/app',
        ]);

        $data = $this->decodeJsonResponse();
        self::assertSame('/app/test', $data['url']);
    }

    public function testUrlIncludesForwardedPrefixAndQueryStringOnXhrVisit(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PREFIX);

        $this->client->request('GET', '/test?page=2', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_FORWARDED_PREFIX' => '/app',
        ]);

        $data = $this->decodeJsonResponse();
        self::assertSame('/app/test?page=2', $data['url']);
    }

    public function testUrlIncludesForwardedPrefixAndQueryStringOnFirstVisit(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PREFIX);

        $this->client->request('GET', '/test?page=2', [], [], [
            'HTTP_X_FORWARDED_PREFIX' => '/app',
        ]);

        $page = $this->extractPageObjectFromHtml();
        self::assertSame('/app/test?page=2', $page['url']);
    }

    public function testUrlIncludesForwardedPrefixWithMultipleQueryParams(): void
    {
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_PREFIX);

        // Symfony normalizeQueryString() sorts params alphabetically: filter < page < sort
        $this->client->request('GET', '/test?page=2&sort=desc&filter=active', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_FORWARDED_PREFIX' => '/app',
        ]);

        $data = $this->decodeJsonResponse();
        self::assertSame('/app/test?filter=active&page=2&sort=desc', $data['url']);
    }

    public function testUntrustedProxyHeaderIsIgnored(): void
    {
        // Proxy not in trusted list — header must be silently ignored.
        $this->client->request('GET', '/test', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_FORWARDED_PREFIX' => '/should-be-ignored',
        ]);

        $data = $this->decodeJsonResponse();
        self::assertSame('/test', $data['url']);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function extractPageObjectFromHtml(): array
    {
        $content = (string) $this->client->getResponse()->getContent();
        $matched = preg_match('/<script[^>]+type="application\/json"[^>]*>([^<]+)<\/script>/s', $content, $matches);
        self::assertSame(1, $matched, '<script type="application/json"> tag not found');
        $page = json_decode($matches[1] ?? '', true);
        self::assertIsArray($page);

        return $page;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(): array
    {
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);

        return $data;
    }
}
