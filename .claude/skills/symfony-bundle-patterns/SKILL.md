---
name: symfony-bundle-patterns
description: Reference patterns for creating Symfony 6.4+ reusable bundles using AbstractBundle. Auto-loaded when working on InertiaBundle.php, config/definition.php, config/services.yaml, or any DI configuration.
---

# Symfony Bundle Patterns (6.4+)

## AbstractBundle — Canonical Pattern

### Main Bundle Class

```php
<?php
declare(strict_types=1);

namespace Nytodev\InertiaBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Main bundle class. Uses AbstractBundle (Symfony 6.1+) — no separate Extension class needed.
 * - configure() defines the config schema (called compile-time only)
 * - loadExtension() loads services and injects config values (called compile-time only)
 */
final class InertiaBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        // Import from separate file to keep this class clean
        $definition->import('../config/definition.php');
    }

    public function loadExtension(
        array $config,  // Already merged & validated by AbstractBundle — use directly
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        // Load service definitions
        $container->import('../config/services.yaml');

        // Inject config values into services
        // Note: physical path used above, not logical '@InertiaBundle/config/...'
        $container->services()
            ->get('inertia.service')
                ->arg('$rootView',   $config['root_view'])
                ->arg('$version',    $config['version'])
                ->arg('$ssrEnabled', $config['ssr_enabled'])
                ->arg('$ssrUrl',     $config['ssr_url'])
        ;
    }
}
```

### Config Definition File

```php
<?php
// config/definition.php
declare(strict_types=1);

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition->rootNode()  // Root key = 'inertia' (bundle alias)
        ->children()
            ->scalarNode('root_view')
                ->defaultValue('base.html.twig')
                ->info('Twig template used as the application root on first visit')
            ->end()
            ->scalarNode('version')
                ->defaultNull()
                ->info('Asset version for cache-busting (string, hash, or null to disable)')
            ->end()
            ->booleanNode('ssr_enabled')
                ->defaultFalse()
                ->info('Enable Server-Side Rendering via a Node.js SSR server')
            ->end()
            ->scalarNode('ssr_url')
                ->defaultValue('http://127.0.0.1:13714')
                ->info('URL of the Node.js SSR server (only used when ssr_enabled is true)')
            ->end()
        ->end()
    ;
};
```

### Services YAML (explicit, no autowiring)

```yaml
# config/services.yaml
services:

    # --- Main service ---
    inertia.service:
        class: Nytodev\InertiaBundle\Service\Inertia
        arguments:
            $requestStack:  '@request_stack'
            $twig:          '@twig'
            $rootView:      'base.html.twig'   # overridden by loadExtension()
            $version:       null               # overridden by loadExtension()
            $ssrEnabled:    false              # overridden by loadExtension()
            $ssrUrl:        'http://127.0.0.1:13714'
        public: false

    # Public alias for autowiring: inject Inertia type-hint in controllers
    Nytodev\InertiaBundle\Service\Inertia:
        alias: inertia.service
        public: true

    # --- Event Listener ---
    inertia.listener:
        class: Nytodev\InertiaBundle\EventListener\InertiaListener
        arguments:
            $inertia: '@inertia.service'
        tags:
            - { name: kernel.event_subscriber }
        public: false

    # --- Twig Extension ---
    inertia.twig_extension:
        class: Nytodev\InertiaBundle\Twig\InertiaTwigExtension
        arguments:
            $inertia: '@inertia.service'
        tags:
            - { name: twig.extension }
        public: false
```

## Config Usage in Applications

```yaml
# config/packages/inertia.yaml (application side)
inertia:
    root_view: 'base.html.twig'
    version: '%env(ASSET_VERSION)%'
    ssr_enabled: '%env(bool:SSR_ENABLED)%'
    ssr_url: '%env(SSR_URL)%'
```

## Key Principles

1. **AbstractBundle over Bundle**: No `DependencyInjection/` directory needed
2. **`$config` is pre-merged**: `loadExtension()` receives flat config, no `processConfiguration()` call
3. **Services prefixed**: `inertia.*` (DI alias = `inertia`)
4. **No autowire/autoconfigure**: All services declared explicitly
5. **Physical paths**: `../config/services.yaml` not `@InertiaBundle/config/...`
6. **Private by default**: Services have `public: false`; only aliases are public
7. **Compile-time only**: Both `configure()` and `loadExtension()` are called at compile time