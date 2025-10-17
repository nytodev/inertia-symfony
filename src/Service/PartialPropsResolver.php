<?php

declare(strict_types=1);

namespace Nytodev\InertiaSymfony\Service;

use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves which props to include/exclude during partial reloads.
 *
 * Handles Inertia.js partial reload headers:
 * - X-Inertia-Partial-Component: Component making the request
 * - X-Inertia-Partial-Data: Comma-separated list of props to include (only)
 * - X-Inertia-Partial-Except: Comma-separated list of props to exclude
 *
 * @see https://inertiajs.com/partial-reloads
 */
final class PartialPropsResolver
{
    /**
     * Check if the request is a partial reload request.
     *
     * A partial reload requires both headers:
     * - X-Inertia-Partial-Component
     * - X-Inertia-Partial-Data
     *
     * @param Request $request The HTTP request
     * @return bool True if partial request, false otherwise
     */
    public function isPartialRequest(Request $request): bool
    {
        return $request->headers->has('X-Inertia-Partial-Component')
            && $request->headers->has('X-Inertia-Partial-Data');
    }

    /**
     * Get the component making the partial request.
     *
     * @param Request $request The HTTP request
     * @return string|null The component name or null if not set
     */
    public function getPartialComponent(Request $request): ?string
    {
        return $request->headers->get('X-Inertia-Partial-Component');
    }

    /**
     * Get list of props to include (X-Inertia-Partial-Data).
     *
     * Returns array of prop keys to include. If this header is present,
     * ONLY these props should be returned (whitelist).
     *
     * @param Request $request The HTTP request
     * @return array<string>|null Array of prop keys or null if header not present
     */
    public function getOnlyProps(Request $request): ?array
    {
        $header = $request->headers->get('X-Inertia-Partial-Data');
        if ($header === null) {
            return null;
        }

        // Split by comma and trim whitespace
        $props = array_map('trim', explode(',', $header));

        // Filter out empty strings
        return array_values(array_filter($props, fn($prop) => $prop !== ''));
    }

    /**
     * Get list of props to exclude (X-Inertia-Partial-Except).
     *
     * Returns array of prop keys to exclude. These props will be
     * removed from the response (blacklist).
     *
     * @param Request $request The HTTP request
     * @return array<string> Array of prop keys to exclude (empty if header not present)
     */
    public function getExceptProps(Request $request): array
    {
        $header = $request->headers->get('X-Inertia-Partial-Except');
        if ($header === null) {
            return [];
        }

        // Split by comma and trim whitespace
        $props = array_map('trim', explode(',', $header));

        // Filter out empty strings
        return array_values(array_filter($props, fn($prop) => $prop !== ''));
    }

    /**
     * Filter props based on partial reload headers.
     *
     * Logic:
     * 1. If not a partial request, return all props
     * 2. If component doesn't match, return all props
     * 3. If "only" list specified, return only those props
     * 4. If "except" list specified, return all props except those
     * 5. Otherwise, return all props
     *
     * @param array<string, mixed> $props The props to filter
     * @param Request $request The HTTP request
     * @param string $currentComponent The current component being rendered
     * @return array<string, mixed> Filtered props
     */
    public function resolveProps(array $props, Request $request, string $currentComponent): array
    {
        // Not a partial request - return all props
        if (!$this->isPartialRequest($request)) {
            return $props;
        }

        // Component mismatch - return all props
        // This prevents partial reloads from affecting navigation to different components
        $partialComponent = $this->getPartialComponent($request);
        if ($partialComponent !== $currentComponent) {
            return $props;
        }

        // Get filtering criteria
        $only = $this->getOnlyProps($request);
        $except = $this->getExceptProps($request);

        // Apply "only" filter (whitelist)
        if ($only !== null) {
            return $this->filterOnly($props, $only);
        }

        // Apply "except" filter (blacklist)
        if (!empty($except)) {
            return $this->filterExcept($props, $except);
        }

        // No filters specified - return all props
        return $props;
    }

    /**
     * Filter props to only include specified keys.
     *
     * @param array<string, mixed> $props The props to filter
     * @param array<string> $only Keys to include
     * @return array<string, mixed> Filtered props
     */
    private function filterOnly(array $props, array $only): array
    {
        return array_intersect_key($props, array_flip($only));
    }

    /**
     * Filter props to exclude specified keys.
     *
     * @param array<string, mixed> $props The props to filter
     * @param array<string> $except Keys to exclude
     * @return array<string, mixed> Filtered props
     */
    private function filterExcept(array $props, array $except): array
    {
        return array_diff_key($props, array_flip($except));
    }
}
