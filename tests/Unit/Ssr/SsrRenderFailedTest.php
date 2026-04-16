<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Ssr;

use Nytodev\InertiaBundle\Ssr\SsrErrorType;
use Nytodev\InertiaBundle\Ssr\SsrRenderFailed;
use PHPUnit\Framework\TestCase;

final class SsrRenderFailedTest extends TestCase
{
    public function testComponentWithComponentInPageReturnsComponent(): void
    {
        $event = new SsrRenderFailed(
            page: ['component' => 'Dashboard', 'url' => '/dashboard'],
            error: 'Test error',
        );

        self::assertSame('Dashboard', $event->component());
    }

    public function testComponentWithMissingComponentReturnsUnknown(): void
    {
        $event = new SsrRenderFailed(
            page: [],
            error: 'Test error',
        );

        self::assertSame('Unknown', $event->component());
    }

    public function testUrlWithUrlInPageReturnsUrl(): void
    {
        $event = new SsrRenderFailed(
            page: ['component' => 'Dashboard', 'url' => '/dashboard'],
            error: 'Test error',
        );

        self::assertSame('/dashboard', $event->url());
    }

    public function testUrlWithMissingUrlReturnsSlash(): void
    {
        $event = new SsrRenderFailed(
            page: [],
            error: 'Test error',
        );

        self::assertSame('/', $event->url());
    }

    public function testDefaultTypeIsUnknown(): void
    {
        $event = new SsrRenderFailed(
            page: ['component' => 'Dashboard'],
            error: 'Something went wrong',
        );

        self::assertSame(SsrErrorType::Unknown, $event->type);
    }

    public function testTypeCanBeOverridden(): void
    {
        $event = new SsrRenderFailed(
            page: ['component' => 'Dashboard'],
            error: 'window is not defined',
            type: SsrErrorType::BrowserApi,
        );

        self::assertSame(SsrErrorType::BrowserApi, $event->type);
    }

    public function testSourceLocationIsStored(): void
    {
        $event = new SsrRenderFailed(
            page: ['component' => 'Dashboard'],
            error: 'window is not defined',
            sourceLocation: '/path/to/Dashboard.vue:10:5',
        );

        self::assertSame('/path/to/Dashboard.vue:10:5', $event->sourceLocation);
    }

    public function testToArrayWithAllFieldsReturnsCompleteArray(): void
    {
        $event = new SsrRenderFailed(
            page: ['component' => 'Dashboard', 'url' => '/dashboard'],
            error: 'window is not defined',
            type: SsrErrorType::BrowserApi,
            hint: 'Wrap in lifecycle hook',
            browserApi: 'window',
            sourceLocation: '/path/to/Dashboard.vue:10:5',
        );

        $array = $event->toArray();

        self::assertSame('Dashboard', $array['component']);
        self::assertSame('/dashboard', $array['url']);
        self::assertSame('window is not defined', $array['error']);
        self::assertSame('browser-api', $array['type']);
        self::assertSame('Wrap in lifecycle hook', $array['hint']);
        self::assertSame('window', $array['browser_api']);
        self::assertSame('/path/to/Dashboard.vue:10:5', $array['source_location']);
    }

    public function testToArrayWithNullOptionalsExcludesNullKeys(): void
    {
        $event = new SsrRenderFailed(
            page: ['component' => 'Dashboard', 'url' => '/dashboard'],
            error: 'Something went wrong',
        );

        $array = $event->toArray();

        self::assertArrayNotHasKey('hint', $array);
        self::assertArrayNotHasKey('browser_api', $array);
        self::assertArrayNotHasKey('source_location', $array);
        self::assertArrayNotHasKey('stack', $array);
    }

    public function testToArrayAlwaysIncludesComponentUrlErrorType(): void
    {
        $event = new SsrRenderFailed(
            page: ['component' => 'Users', 'url' => '/users'],
            error: 'Render failed',
            type: SsrErrorType::Render,
        );

        $array = $event->toArray();

        self::assertArrayHasKey('component', $array);
        self::assertArrayHasKey('url', $array);
        self::assertArrayHasKey('error', $array);
        self::assertArrayHasKey('type', $array);
    }
}
