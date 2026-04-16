<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Ssr;

use Nytodev\InertiaBundle\Ssr\SsrGatewayInterface;
use Nytodev\InertiaBundle\Ssr\SsrResponse;
use Nytodev\InertiaBundle\Ssr\SsrState;
use PHPUnit\Framework\TestCase;

final class SsrStateTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $page = ['component' => 'Home', 'props' => [], 'url' => '/'];

    public function testDispatchCallsGatewayOnce(): void
    {
        $response = new SsrResponse('<title>Home</title>', '<div id="app"></div>');

        $gateway = $this->createMock(SsrGatewayInterface::class);
        $gateway->expects(self::once())
            ->method('dispatch')
            ->with($this->page)
            ->willReturn($response);

        $state = new SsrState($gateway);
        $state->setPage($this->page);

        $result1 = $state->dispatch();
        $result2 = $state->dispatch(); // second call must NOT re-dispatch

        self::assertSame($response, $result1);
        self::assertSame($response, $result2);
    }

    public function testDispatchReturnsNullWhenGatewayReturnsNull(): void
    {
        $gateway = $this->createMock(SsrGatewayInterface::class);
        $gateway->method('dispatch')->willReturn(null);

        $state = new SsrState($gateway);
        $state->setPage($this->page);

        self::assertNull($state->dispatch());
    }

    public function testDispatchCachesNullResult(): void
    {
        $gateway = $this->createMock(SsrGatewayInterface::class);
        $gateway->expects(self::once())
            ->method('dispatch')
            ->willReturn(null);

        $state = new SsrState($gateway);
        $state->setPage($this->page);

        $state->dispatch();
        $state->dispatch(); // must not call gateway again
    }

    public function testSetPageReturnsSelf(): void
    {
        $gateway = $this->createMock(SsrGatewayInterface::class);
        $state = new SsrState($gateway);

        self::assertSame($state, $state->setPage($this->page));
    }

    public function testDispatchBeforeSetPageUsesEmptyPage(): void
    {
        $gateway = $this->createMock(SsrGatewayInterface::class);
        $gateway->expects(self::once())
            ->method('dispatch')
            ->with([]) // empty page
            ->willReturn(null);

        $state = new SsrState($gateway);
        $state->dispatch();
    }

    public function testResetAllowsRedispatch(): void
    {
        $response = new SsrResponse('<title>T</title>', '<div id="app"></div>');

        $gateway = $this->createMock(SsrGatewayInterface::class);
        $gateway->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturn($response);

        $state = new SsrState($gateway);
        $state->setPage($this->page);

        $state->dispatch(); // first dispatch
        $state->reset();    // clear state
        $state->dispatch(); // must dispatch again
    }

    public function testResetClearsPage(): void
    {
        $gateway = $this->createMock(SsrGatewayInterface::class);
        $gateway->expects(self::once())
            ->method('dispatch')
            ->with([]) // page cleared after reset
            ->willReturn(null);

        $state = new SsrState($gateway);
        $state->setPage($this->page);
        $state->reset();
        $state->dispatch();
    }
}
