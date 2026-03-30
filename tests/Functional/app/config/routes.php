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

    $routes->add('test_inertia_merge', '/test/merge')
        ->controller(['Nytodev\InertiaBundle\Tests\Functional\TestController', 'merge']);

    $routes->add('test_inertia_always', '/test/always')
        ->controller(['Nytodev\InertiaBundle\Tests\Functional\TestController', 'always']);

    $routes->add('test_inertia_defer', '/test/defer')
        ->controller(['Nytodev\InertiaBundle\Tests\Functional\TestController', 'defer']);

    $routes->add('test_inertia_clear_history', '/test/clear-history')
        ->controller(['Nytodev\InertiaBundle\Tests\Functional\TestController', 'clearHistory']);

    $routes->add('test_inertia_encrypt_history', '/test/encrypt-history')
        ->controller(['Nytodev\InertiaBundle\Tests\Functional\TestController', 'encryptHistory']);

    $routes->add('test_inertia_match_props_on', '/test/match-props-on')
        ->controller(['Nytodev\InertiaBundle\Tests\Functional\TestController', 'matchPropsOn']);
};
