<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\app;

use Nytodev\InertiaBundle\InertiaBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Test kernel variant with a fixed asset version configured.
 * A compiler pass sets $version on inertia.service AFTER InertiaBundle::loadExtension()
 * has run — mirrors the SsrKernel pattern.
 */
final class VersionedKernel extends Kernel
{
    public function __construct(private readonly string $assetVersion = 'v1')
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

        $assetVersion = $this->assetVersion;

        $container->addCompilerPass(new class($assetVersion) implements CompilerPassInterface {
            public function __construct(private readonly string $version)
            {
            }

            public function process(ContainerBuilder $container): void
            {
                $container->getDefinition('inertia.service')
                    ->replaceArgument('$version', $this->version);
            }
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/inertia-bundle-tests/cache/versioned-'.$this->assetVersion;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/inertia-bundle-tests/logs';
    }
}
