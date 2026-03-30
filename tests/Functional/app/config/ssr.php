<?php

declare(strict_types=1);

use Nytodev\InertiaBundle\Tests\Functional\app\Ssr\StubSsrGateway;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/*
 * SSR test configuration: overrides the SSR gateway with a test stub.
 * Loaded only by SsrKernel.
 */
return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('inertia.ssr_gateway', StubSsrGateway::class);
};
