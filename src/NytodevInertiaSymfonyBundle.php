<?php

declare(strict_types=1);

namespace Nytodev\InertiaSymfony;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Inertia.js integration bundle for Symfony 6.4+.
 *
 * This bundle provides seamless integration between Symfony and Inertia.js,
 * allowing you to build modern single-page applications using server-side
 * routing and controllers.
 *
 * @see https://inertiajs.com
 */
final class NytodevInertiaSymfonyBundle extends AbstractBundle
{
    /**
     * {@inheritdoc}
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('version')
                    ->defaultValue('1.0.0')
                    ->info('Static asset version string (e.g., "v1.0.0")')
                ->end()
                ->scalarNode('root_template')
                    ->defaultValue('@NytodevInertiaSymfony/inertia.html.twig')
                    ->info('Path to the root Inertia template')
                ->end()
            ->end()
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<string, mixed> $config
     */
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        // Import service definitions
        $container->import('../config/services.yaml');

        // Configure services with user configuration
        $container->services()
            ->get('nytodev_inertia.version_strategy')
            ->arg('$version', $config['version'])
        ;

        $container->services()
            ->get('nytodev_inertia.response_factory')
            ->arg('$rootTemplate', $config['root_template'])
        ;
    }
}
