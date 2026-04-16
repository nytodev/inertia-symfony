<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Ssr;

use Nytodev\InertiaBundle\Ssr\SsrErrorType;
use PHPUnit\Framework\TestCase;

final class SsrErrorTypeTest extends TestCase
{
    public function testFromStringWithNullReturnsUnknown(): void
    {
        self::assertSame(SsrErrorType::Unknown, SsrErrorType::fromString(null));
    }

    public function testFromStringWithUnknownStringReturnsUnknown(): void
    {
        self::assertSame(SsrErrorType::Unknown, SsrErrorType::fromString('not-a-type'));
    }

    public function testFromStringWithBrowserApiReturnsBrowserApi(): void
    {
        self::assertSame(SsrErrorType::BrowserApi, SsrErrorType::fromString('browser-api'));
    }

    public function testFromStringWithComponentResolutionReturnsComponentResolution(): void
    {
        self::assertSame(SsrErrorType::ComponentResolution, SsrErrorType::fromString('component-resolution'));
    }

    public function testFromStringWithRenderReturnsRender(): void
    {
        self::assertSame(SsrErrorType::Render, SsrErrorType::fromString('render'));
    }

    public function testFromStringWithConnectionReturnsConnection(): void
    {
        self::assertSame(SsrErrorType::Connection, SsrErrorType::fromString('connection'));
    }

    public function testFromStringWithUnknownReturnsUnknown(): void
    {
        self::assertSame(SsrErrorType::Unknown, SsrErrorType::fromString('unknown'));
    }

    public function testValueAllCasesHaveCorrectStringValue(): void
    {
        self::assertSame('browser-api', SsrErrorType::BrowserApi->value);
        self::assertSame('component-resolution', SsrErrorType::ComponentResolution->value);
        self::assertSame('render', SsrErrorType::Render->value);
        self::assertSame('connection', SsrErrorType::Connection->value);
        self::assertSame('unknown', SsrErrorType::Unknown->value);
    }
}
