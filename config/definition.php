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
            ->booleanNode('encrypt_history')
                ->defaultFalse()
                ->info('Globally enable browser history encryption for all Inertia responses. Can be overridden per-render via Inertia::encryptHistory().')
            ->end()
            ->booleanNode('expose_shared_prop_keys')
                ->defaultTrue()
                ->info('When true, the page object includes a sharedProps field listing keys coming from Inertia::share(). Allows the client to distinguish shared from page-specific props.')
            ->end()
            ->booleanNode('ssr_enabled')
                ->defaultFalse()
                ->info('Enable Server-Side Rendering via an external Node.js SSR server.')
            ->end()
            ->scalarNode('ssr_url')
                ->defaultValue('http://127.0.0.1:13714')
                ->info('URL of the SSR server (used only when ssr_enabled is true).')
            ->end()
            ->scalarNode('ssr_bundle')
                ->defaultNull()
                ->info('Path to the SSR bundle JS file. If null, auto-detected from common paths (bootstrap/ssr/ssr.mjs, public/build/ssr/ssr.mjs).')
            ->end()
        ->end()
    ;
};
