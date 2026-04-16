<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Twig;

use Nytodev\InertiaBundle\Ssr\NullSsrGateway;
use Nytodev\InertiaBundle\Ssr\SsrGatewayInterface;
use Nytodev\InertiaBundle\Ssr\SsrResponse;
use Nytodev\InertiaBundle\Ssr\SsrState;
use Nytodev\InertiaBundle\Twig\InertiaTwigExtension;
use PHPUnit\Framework\TestCase;

final class InertiaTwigExtensionTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $page = ['component' => 'Home', 'props' => [], 'url' => '/'];

    private function makeExt(?SsrGatewayInterface $gateway = null): InertiaTwigExtension
    {
        return new InertiaTwigExtension(new SsrState($gateway ?? new NullSsrGateway()));
    }

    // -------------------------------------------------------------------------
    // SSR disabled (NullSsrGateway)
    // -------------------------------------------------------------------------

    public function testRenderInertiaNoSsrReturnsDataPageDiv(): void
    {
        $html = $this->makeExt()->renderInertia($this->page);

        self::assertStringContainsString('<div id="app"', $html);
        self::assertStringContainsString('data-page=', $html);
    }

    public function testRenderInertiaNoSsrJsonEscapesXssChars(): void
    {
        $html = $this->makeExt()->renderInertia(['component' => '<script>', 'props' => []]);

        self::assertStringNotContainsString('<script>', $html);
    }

    public function testRenderInertiaHeadNoSsrReturnsEmptyString(): void
    {
        self::assertSame('', $this->makeExt()->renderInertiaHead($this->page));
    }

    // -------------------------------------------------------------------------
    // SSR enabled
    // -------------------------------------------------------------------------

    public function testRenderInertiaWithSsrReturnsSsrBody(): void
    {
        $gateway = $this->createStub(SsrGatewayInterface::class);
        $gateway->method('dispatch')->willReturn(
            new SsrResponse('<title>SSR Title</title>', '<div id="app"><p>rendered</p></div>'),
        );

        $html = $this->makeExt($gateway)->renderInertia($this->page);

        self::assertSame('<div id="app"><p>rendered</p></div>', $html);
    }

    public function testRenderInertiaHeadWithSsrReturnsSsrHead(): void
    {
        $gateway = $this->createStub(SsrGatewayInterface::class);
        $gateway->method('dispatch')->willReturn(
            new SsrResponse('<title>SSR Title</title>', '<div id="app"></div>'),
        );
        $ext = $this->makeExt($gateway);
        $ext->renderInertia($this->page); // dispatch happens here

        $head = $ext->renderInertiaHead($this->page);

        self::assertSame('<title>SSR Title</title>', $head);
    }

    public function testRenderInertiaHeadCalledBeforeRenderInertiaDispatchesOnce(): void
    {
        $dispatchCount = 0;
        $gateway = $this->createMock(SsrGatewayInterface::class);
        $gateway->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function () use (&$dispatchCount): SsrResponse {
                ++$dispatchCount;

                return new SsrResponse('<title>T</title>', '<div id="app"></div>');
            });

        $ext = $this->makeExt($gateway);
        $ext->renderInertiaHead($this->page); // first call — dispatches
        $ext->renderInertia($this->page);     // second call — uses cache

        self::assertSame(1, $dispatchCount);
    }

    public function testRenderInertiaSsrGatewayReturnsNullFallsBackToDataPageDiv(): void
    {
        $gateway = $this->createStub(SsrGatewayInterface::class);
        $gateway->method('dispatch')->willReturn(null);

        $html = $this->makeExt($gateway)->renderInertia($this->page);

        self::assertStringContainsString('data-page=', $html);
    }

    public function testRenderInertiaHeadSsrGatewayReturnsNullReturnsEmptyString(): void
    {
        $gateway = $this->createStub(SsrGatewayInterface::class);
        $gateway->method('dispatch')->willReturn(null);

        self::assertSame('', $this->makeExt($gateway)->renderInertiaHead($this->page));
    }

    // -------------------------------------------------------------------------
    // Misc
    // -------------------------------------------------------------------------

    public function testGetFunctionsReturnsTwoFunctions(): void
    {
        self::assertCount(2, $this->makeExt()->getFunctions());
    }
}
