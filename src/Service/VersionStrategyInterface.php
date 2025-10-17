<?php

declare(strict_types=1);

namespace Nytodev\InertiaSymfony\Service;

/**
 * Interface for asset versioning strategies.
 *
 * Inertia.js uses asset versions to detect when client-side assets are stale
 * and need to be reloaded. When the version changes, the client performs
 * a full page reload to fetch the new assets.
 *
 * @see https://inertiajs.com/asset-versioning
 */
interface VersionStrategyInterface
{
    /**
     * @return string
     */
    public function getVersion(): string;
}
