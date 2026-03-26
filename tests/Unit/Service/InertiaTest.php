<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Service;

use Nytodev\InertiaBundle\Service\Inertia;
use PHPUnit\Framework\TestCase;

final class InertiaTest extends TestCase
{
    public function testRender_WithBasicComponent_ReturnsResponse(): void
    {
    }

    public function testRender_WithInertiaXhrRequest_ReturnsJsonResponse(): void
    {
    }

    public function testShare_WithKeyValue_SharedPropAvailableOnNextRender(): void
    {
    }

    public function testShareOnce_WithKeyValue_PropClearedAfterFirstRender(): void
    {
    }

    public function testVersion_WhenConfigured_ReturnsVersionString(): void
    {
    }

    public function testVersion_WhenNotConfigured_ReturnsNull(): void
    {
    }

    public function testGetSharedProps_ReturnsAllSharedProps(): void
    {
    }

    public function testFlushSharedOnceProps_ClearsOnceProps(): void
    {
    }
}
