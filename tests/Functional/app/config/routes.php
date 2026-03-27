<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->add('test_inertia', '/test')
        ->controller(['Nytodev\InertiaBundle\Tests\Functional\TestController', 'index']);

    $routes->add('test_inertia_redirect', '/test/redirect')
        ->controller(['Nytodev\InertiaBundle\Tests\Functional\TestController', 'redirect'])
        ->methods(['POST', 'PUT', 'PATCH', 'DELETE']);

    $routes->add('test_inertia_shared', '/test/shared')
        ->controller(['Nytodev\InertiaBundle\Tests\Functional\TestController', 'shared']);

    $routes->add('test_inertia_partial', '/test/partial')
        ->controller(['Nytodev\InertiaBundle\Tests\Functional\TestController', 'partial']);

    $routes->add('test_inertia_lazy', '/test/lazy')
        ->controller(['Nytodev\InertiaBundle\Tests\Functional\TestController', 'lazy']);
};
