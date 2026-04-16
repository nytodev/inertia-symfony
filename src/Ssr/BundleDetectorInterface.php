<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Ssr;

interface BundleDetectorInterface
{
    /**
     * Detect and return the absolute path to the SSR bundle file, or null if not found.
     */
    public function detect(): ?string;
}
