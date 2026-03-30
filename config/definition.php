<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()
        ->children()
            ->scalarNode('root_view')
                ->defaultValue('base.html.twig')
                ->cannotBeEmpty()
                ->info('The root Twig template used to render the full HTML page on first visit.')
            ->end()
            ->scalarNode('version')
                ->defaultNull()
                ->info('Asset version string. When it changes, Inertia triggers a full page reload. Set to null to disable.')
            ->end()
            ->booleanNode('ssr_enabled')
                ->defaultFalse()
                ->info('Enable Server-Side Rendering via an external Node.js SSR server.')
            ->end()
            ->scalarNode('ssr_url')
                ->defaultValue('http://127.0.0.1:13714')
                ->info('URL of the SSR server (used only when ssr_enabled is true).')
            ->end()
        ->end()
    ;
};
