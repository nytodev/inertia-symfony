<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
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
        $container->parameters()
            ->set('inertia.root_view', $config['root_view'])
            ->set('inertia.version', $config['version'])
            ->set('inertia.ssr_enabled', $config['ssr_enabled'])
            ->set('inertia.ssr_url', $config['ssr_url']);
    }
}
