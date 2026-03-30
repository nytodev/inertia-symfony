<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\app;

use Nytodev\InertiaBundle\InertiaBundle;
use Nytodev\InertiaBundle\Tests\Functional\app\Ssr\StubSsrGateway;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Test kernel variant with SSR enabled via a stub gateway.
 * A compiler pass replaces inertia.ssr_gateway with StubSsrGateway AFTER
 * InertiaBundle::loadExtension() has run — the only reliable way to override
 * a service set by a bundle extension.
 */
final class SsrKernel extends Kernel
{
    public function __construct()
    {
        parent::__construct('test', false);
    }

    /**
     * @return iterable<\Symfony\Component\HttpKernel\Bundle\BundleInterface>
     */
    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new TwigBundle(), new InertiaBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config/framework.php');
        $loader->load(__DIR__.'/config/twig.php');
        $loader->load(__DIR__.'/config/routing.php');
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $container->setDefinition(
                    'inertia.ssr_gateway',
                    (new Definition(StubSsrGateway::class))->setPublic(false),
                );
            }
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/inertia-bundle-tests/cache/ssr';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/inertia-bundle-tests/logs';
    }
}
