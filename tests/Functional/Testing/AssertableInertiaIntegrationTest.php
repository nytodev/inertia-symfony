<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Testing;

use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

/**
 * Integration tests for assertInertia() + AssertableInertiaPage
 * against the real test kernel and TestController.
 */
final class AssertableInertiaIntegrationTest extends FunctionalTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    // ── XHR (JSON) response ───────────────────────────────────────────────────

    public function testAssertInertiaWithXhrResponseParsesPageObject(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $response = $this->client->getResponse();

        $this->assertInertia($response, static function ($page): void {
            $page->component('TestComponent');
        });
    }

    public function testAssertInertiaWithXhrResponseCanAssertUrl(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $response = $this->client->getResponse();

        $this->assertInertia($response, static function ($page): void {
            $page->url('/test');
        });
    }

    public function testAssertInertiaWithXhrResponseCanAssertVersion(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $response = $this->client->getResponse();

        $this->assertInertia($response, static function ($page): void {
            $page->version(null); // no version configured in test kernel
        });
    }

    public function testAssertInertiaWithXhrResponseCanAssertProps(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $response = $this->client->getResponse();

        $this->assertInertia($response, static function ($page): void {
            $page
                ->has('foo')
                ->where('foo', 'bar')
                ->has('errors');
        });
    }

    // ── HTML (first-visit) response ───────────────────────────────────────────

    public function testAssertInertiaWithHtmlResponseParsesDataPageAttribute(): void
    {
        $this->client->request('GET', '/test');
        $response = $this->client->getResponse();

        $this->assertInertia($response, static function ($page): void {
            $page->component('TestComponent');
        });
    }

    public function testAssertInertiaWithHtmlResponseCanAssertProps(): void
    {
        $this->client->request('GET', '/test');
        $response = $this->client->getResponse();

        $this->assertInertia($response, static function ($page): void {
            $page
                ->has('foo')
                ->where('foo', 'bar');
        });
    }

    // ── chaining ─────────────────────────────────────────────────────────────

    public function testAssertInertiaFluentChainingAllMethodsPasses(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $response = $this->client->getResponse();

        $this->assertInertia($response, static function ($page): void {
            $page
                ->component('TestComponent')
                ->url('/test')
                ->version(null)
                ->has('foo')
                ->missing('nonexistent_prop')
                ->where('foo', 'bar')
                ->whereAll(['foo' => 'bar'])
                ->has('errors');
        });
    }

    // ── failure propagation ───────────────────────────────────────────────────

    public function testAssertInertiaFailingAssertionPropagatesAssertionError(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $response = $this->client->getResponse();

        $this->expectException(AssertionFailedError::class);

        $this->assertInertia($response, static function ($page): void {
            $page->component('WrongComponent');
        });
    }

    // ── nested props ─────────────────────────────────────────────────────────

    public function testAssertInertiaWithPartialRouteCanUseDotNotation(): void
    {
        $this->client->request('GET', '/test/partial', [], [], ['HTTP_X_INERTIA' => 'true']);
        $response = $this->client->getResponse();

        $this->assertInertia($response, static function ($page): void {
            $page
                ->has('foo')
                ->has('errors');
        });
    }
}
