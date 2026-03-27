<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'router' => [
            'resource' => __DIR__.'/routes.php',
            'type' => 'php',
        ],
    ]);
};
