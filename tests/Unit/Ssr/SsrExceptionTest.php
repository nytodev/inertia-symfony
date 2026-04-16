<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Ssr;

use Nytodev\InertiaBundle\Ssr\SsrErrorType;
use Nytodev\InertiaBundle\Ssr\SsrException;
use Nytodev\InertiaBundle\Ssr\SsrRenderFailed;
use PHPUnit\Framework\TestCase;

final class SsrExceptionTest extends TestCase
{
    private function makeEvent(
        string $component = 'Foo/Bar',
        string $error = 'window is not defined',
        SsrErrorType $type = SsrErrorType::BrowserApi,
        ?string $hint = null,
        ?string $browserApi = null,
        ?string $sourceLocation = null,
    ): SsrRenderFailed {
        return new SsrRenderFailed(
            page: ['component' => $component, 'url' => '/test'],
            error: $error,
            type: $type,
            hint: $hint,
            browserApi: $browserApi,
            sourceLocation: $sourceLocation,
        );
    }

    public function testFromEventBuildsMessageWithComponentAndError(): void
    {
        $event = $this->makeEvent();
        $exception = SsrException::fromEvent($event);

        self::assertStringContainsString('Foo/Bar', $exception->getMessage());
        self::assertStringContainsString('window is not defined', $exception->getMessage());
    }

    public function testFromEventMessageContainsSourceLocationWhenPresent(): void
    {
        $event = $this->makeEvent(sourceLocation: 'resources/js/Pages/Dashboard.vue:10:5');
        $exception = SsrException::fromEvent($event);

        self::assertStringContainsString('resources/js/Pages/Dashboard.vue:10:5', $exception->getMessage());
    }

    public function testFromEventMessageOmitsSourceLocationWhenAbsent(): void
    {
        $event = $this->makeEvent(sourceLocation: null);
        $exception = SsrException::fromEvent($event);

        // Message must not contain "at null" or similar
        self::assertStringNotContainsString(' at ', $exception->getMessage());
    }

    public function testComponentDelegatesToEvent(): void
    {
        $event = $this->makeEvent(component: 'Pages/Profile');
        $exception = SsrException::fromEvent($event);

        self::assertSame('Pages/Profile', $exception->component());
    }

    public function testTypeDelegatesToEvent(): void
    {
        $event = $this->makeEvent(type: SsrErrorType::Connection);
        $exception = SsrException::fromEvent($event);

        self::assertSame(SsrErrorType::Connection, $exception->type());
    }

    public function testHintDelegatesToEvent(): void
    {
        $event = $this->makeEvent(hint: 'Wrap in onMounted()');
        $exception = SsrException::fromEvent($event);

        self::assertSame('Wrap in onMounted()', $exception->hint());
    }

    public function testSourceLocationDelegatesToEvent(): void
    {
        $event = $this->makeEvent(sourceLocation: 'src/App.vue:5:1');
        $exception = SsrException::fromEvent($event);

        self::assertSame('src/App.vue:5:1', $exception->sourceLocation());
    }

    public function testIsRuntimeException(): void
    {
        $event = $this->makeEvent();
        $exception = SsrException::fromEvent($event);

        self::assertInstanceOf(\RuntimeException::class, $exception);
    }
}
