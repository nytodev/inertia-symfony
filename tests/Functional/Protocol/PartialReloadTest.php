<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PartialReloadTest extends WebTestCase
{
    public function testPartialReload_WithPartialDataHeader_ReturnsOnlyRequestedProps(): void
    {
    }

    public function testPartialReload_WithPartialExceptHeader_ExcludesListedProps(): void
    {
    }

    public function testPartialReload_ErrorsPropAlwaysIncluded(): void
    {
    }

    public function testPartialReload_LazyPropResolvedWhenRequested(): void
    {
    }

    public function testPartialReload_LazyPropSkippedWhenNotRequested(): void
    {
    }

    public function testPartialReload_ExceptWinsOverOnly_WhenBothPresent(): void
    {
    }
}
