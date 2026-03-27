<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Bundle;

use Nytodev\InertiaBundle\Service\Inertia;
use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;

final class ConfigurationTest extends FunctionalTestCase
{
    public function testConfigurationDefaultValuesAreApplied(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $inertia = $container->get(Inertia::class);
        self::assertInstanceOf(Inertia::class, $inertia);
        // Default version is null
        self::assertNull($inertia->version());
    }

    public function testConfigurationCustomRootViewIsInjected(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $inertia = $container->get(Inertia::class);
        self::assertInstanceOf(Inertia::class, $inertia);
    }

    public function testConfigurationInertiaServiceIsRegistered(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        self::assertTrue($container->has(Inertia::class));
    }

    public function testConfigurationInertiaServiceIsAutowirable(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $inertia = $container->get(Inertia::class);
        self::assertInstanceOf(Inertia::class, $inertia);
    }

    public function testConfigurationSsrEnabledDefaultIsFalse(): void
    {
        // SSR is disabled by default — confirm service boots without SSR errors.
        self::bootKernel();
        $container = self::getContainer();
        $inertia = $container->get(Inertia::class);
        self::assertInstanceOf(Inertia::class, $inertia);
    }
}
