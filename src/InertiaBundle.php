<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle;

use Nytodev\InertiaBundle\Ssr\HttpSsrGateway;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Main bundle class. Uses AbstractBundle (Symfony 6.1+) — no separate Extension class needed.
 *
 * configure()     → defines the config schema (compile-time only)
 * loadExtension() → loads services and injects config values (compile-time only)
 *
 * @see config/definition.php  Config schema
 * @see config/services.yaml   Service definitions
 */
final class InertiaBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        // $config is already merged and validated by AbstractBundle — use directly.
        // Never call processConfiguration() here.
        // Inject config values directly onto service definitions (not as global container parameters).
        $services = $container->services();

        $services->get('inertia.response')
            ->arg('$rootView', $config['root_view']);

        $services->get('inertia.service')
            ->arg('$version', $config['version']);

        // SSR: when enabled, replace NullSsrGateway with the real HTTP gateway.
        if ($config['ssr_enabled']) {
            $services->get('inertia.ssr_gateway')
                ->class(HttpSsrGateway::class)
                ->arg('$httpClient', service('http_client'))
                ->arg('$ssrUrl', $config['ssr_url']);
        }
    }
}
