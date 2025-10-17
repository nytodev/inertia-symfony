<?php

declare(strict_types=1);

namespace Nytodev\InertiaSymfony\Service;

use Nytodev\InertiaSymfony\Response\InertiaResponse;
use Nytodev\InertiaSymfony\Response\InertiaResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Main service for rendering Inertia.js pages from controllers.
 *
 * This is the primary API that developers will use in their controllers
 * to create Inertia responses.
 *
 * Example usage:
 * ```php
 * $inertiaManager->render('Dashboard/Index', [
 *     'user' => $user,
 *     'posts' => $posts,
 * ]);
 * ```
 */
final class InertiaManager
{
    /**
     * Shared props that will be included in all Inertia responses.
     *
     * @var array<string, mixed>
     */
    private array $sharedProps = [];

    /**
     * @param InertiaResponseFactory $responseFactory Factory for creating HTTP responses
     * @param VersionStrategyInterface $versionStrategy Asset versioning strategy
     * @param RequestStack $requestStack Symfony request stack
     */
    public function __construct(
        private readonly InertiaResponseFactory $responseFactory,
        private readonly VersionStrategyInterface $versionStrategy,
        private readonly RequestStack $requestStack
    ) {
    }

    /**
     * Render an Inertia page.
     *
     * @param string $component The frontend component name (e.g., 'Dashboard/Index')
     * @param array<string, mixed> $props Props to pass to the component
     * @return Response The HTTP response
     */
    public function render(string $component, array $props = []): Response
    {
        $request = $this->getCurrentRequest();

        // Merge shared props with page-specific props
        $allProps = array_merge($this->sharedProps, $props);

        // Create InertiaResponse DTO
        $inertiaResponse = new InertiaResponse(
            component: $component,
            props: $allProps,
            url: $request->getRequestUri(),
            version: $this->versionStrategy->getVersion()
        );

        // Create HTTP response
        return $this->responseFactory->create($inertiaResponse, $request);
    }

    /**
     * Share props that will be included in all Inertia responses.
     *
     * Shared props are useful for data that needs to be available on every page,
     * such as authenticated user info, flash messages, or app-wide settings.
     *
     * Usage:
     * - Single prop: $inertia->share('key', 'value')
     * - Multiple props: $inertia->share(['key1' => 'value1', 'key2' => 'value2'])
     *
     * @param string|array<string, mixed> $key The prop key or array of props
     * @param mixed $value The prop value (ignored if $key is an array)
     * @return self For method chaining
     */
    public function share(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->sharedProps = array_merge($this->sharedProps, $key);
        } else {
            $this->sharedProps[$key] = $value;
        }

        return $this;
    }

    /**
     * Get all currently shared props.
     *
     * @return array<string, mixed> The shared props
     */
    public function getSharedProps(): array
    {
        return $this->sharedProps;
    }

    /**
     * Clear all shared props.
     *
     * @return self For method chaining
     */
    public function clearSharedProps(): self
    {
        $this->sharedProps = [];

        return $this;
    }

    /**
     * Get the current request from the request stack.
     *
     * @return Request The current request
     * @throws \RuntimeException If no request is available
     */
    private function getCurrentRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new \RuntimeException('No request available in the request stack.');
        }

        return $request;
    }
}
