<?php

declare(strict_types=1);

namespace Nytodev\InertiaSymfony\Service;

final class StaticVersionStrategy implements VersionStrategyInterface
{
    /**
     * @param string $version The static version string (e.g., "v1.0.0")
     */
    public function __construct(
        private readonly string $version
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return $this->version;
    }
}
