<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Service;

use Nytodev\InertiaBundle\Props\DeferProp;
use Nytodev\InertiaBundle\Props\LazyProp;
use Nytodev\InertiaBundle\Props\MergeProp;
use Nytodev\InertiaBundle\Props\OnceProp;
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
        /** @phpstan-ignore property.onlyWritten (reserved for SSR phase 2) */
        private readonly Environment $twig,
        private readonly InertiaResponse $inertiaResponse,
        /** @phpstan-ignore property.onlyWritten (reserved for SSR phase 2) */
        private readonly string $rootView,
        private readonly ?string $version,
        /** @phpstan-ignore property.onlyWritten (reserved for SSR phase 2) */
        private readonly bool $ssrEnabled,
        /** @phpstan-ignore property.onlyWritten (reserved for SSR phase 2) */
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
        $request = $this->requestStack->getCurrentRequest()
            ?? throw new \LogicException('No current request.');

        $mergedProps = array_merge($this->sharedProps, $this->sharedOnceProps, $props);

        return $this->inertiaResponse->build(
            $component,
            $mergedProps,
            $request->getRequestUri(),
            $this->version,
            $request,
        );
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

    public function lazy(\Closure $callback): LazyProp
    {
        return new LazyProp($callback);
    }

    public function defer(\Closure $callback, string $group = 'default'): DeferProp
    {
        return new DeferProp($callback, $group);
    }

    public function once(\Closure $callback): OnceProp
    {
        return new OnceProp($callback);
    }

    public function merge(\Closure $callback, bool $prepend = false, bool $deep = false): MergeProp
    {
        return new MergeProp($callback, $prepend, $deep);
    }
}
