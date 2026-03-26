<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Service;

use Nytodev\InertiaBundle\Response\InertiaResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Main Inertia service. Manages shared props and delegates response building
 * to InertiaResponse.
 */
final class Inertia
{
    /** @var array<string, mixed> */
    private array $sharedProps = [];

    /** @var array<string, mixed> */
    private array $sharedOnceProps = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Environment $twig,
        private readonly InertiaResponse $inertiaResponse,
        private readonly string $rootView,
        private readonly ?string $version,
        private readonly bool $ssrEnabled,
        private readonly string $ssrUrl,
    ) {
    }

    /**
     * Render an Inertia component. Merges shared props with the given props
     * and returns either an HTML or JSON response based on the request headers.
     *
     * @param array<string, mixed> $props
     */
    public function render(string $component, array $props = [], ?Response $response = null): Response
    {
        // TODO: implement
    }

    /**
     * Share a prop on every subsequent Inertia render for this request lifecycle.
     */
    public function share(string $key, mixed $value): void
    {
        $this->sharedProps[$key] = $value;
    }

    /**
     * Share a prop only on the next Inertia render, then discard it.
     */
    public function shareOnce(string $key, mixed $value): void
    {
        $this->sharedOnceProps[$key] = $value;
    }

    /**
     * Return the configured asset version string (null if versioning is disabled).
     */
    public function version(): ?string
    {
        return $this->version;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSharedProps(): array
    {
        return $this->sharedProps;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSharedOnceProps(): array
    {
        return $this->sharedOnceProps;
    }

    /**
     * Clear all once-props (called by InertiaListener after the response is sent).
     */
    public function flushSharedOnceProps(): void
    {
        $this->sharedOnceProps = [];
    }
}
