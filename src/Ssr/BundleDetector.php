<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Ssr;

/**
 * Detects the path to the SSR bundle file.
 * Checks the configured path first, then falls back to known candidate locations.
 */
final class BundleDetector implements BundleDetectorInterface
{
    /** @var list<string> Relative paths searched under the project root when no explicit bundle is configured. */
    private const CANDIDATES = [
        'bootstrap/ssr/ssr.mjs',
        'public/build/ssr/ssr.mjs',
        'public/build/ssr/ssr.js',
    ];

    public function __construct(
        private readonly ?string $ssrBundle,
        private readonly string $projectDir,
    ) {
    }

    public function detect(): ?string
    {
        if (null !== $this->ssrBundle) {
            return file_exists($this->ssrBundle) ? $this->ssrBundle : null;
        }

        foreach (self::CANDIDATES as $relative) {
            $absolute = $this->projectDir.\DIRECTORY_SEPARATOR.$relative;
            if (file_exists($absolute)) {
                return $absolute;
            }
        }

        return null;
    }
}
