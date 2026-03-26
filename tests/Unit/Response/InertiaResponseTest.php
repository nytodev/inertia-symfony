<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Response;

use Nytodev\InertiaBundle\Response\InertiaResponse;
use PHPUnit\Framework\TestCase;

final class InertiaResponseTest extends TestCase
{
    public function testBuild_WithFirstVisitRequest_ReturnsHtmlResponse(): void
    {
    }

    public function testBuild_WithXhrRequest_ReturnsJsonResponse(): void
    {
    }

    public function testBuildPageObject_AlwaysIncludesClearHistoryAndEncryptHistory(): void
    {
    }

    public function testBuildPageObject_JsonEncodesWithXssProtectionFlags(): void
    {
    }

    public function testResolveProps_WithLazyProp_SkipsOnFullRender(): void
    {
    }

    public function testResolveProps_WithLazyProp_ResolvesOnPartialReload(): void
    {
    }

    public function testResolveProps_WithDeferProp_ExcludesFromPropsArray(): void
    {
    }

    public function testResolveProps_ErrorsPropAlwaysPresent(): void
    {
    }
}
