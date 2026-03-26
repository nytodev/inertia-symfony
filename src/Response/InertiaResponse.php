<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Response;

use Nytodev\InertiaBundle\Props\DeferProp;
use Nytodev\InertiaBundle\Props\LazyProp;
use Nytodev\InertiaBundle\Props\MergeProp;
use Nytodev\InertiaBundle\Props\OnceProp;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Builds the Inertia page object and returns the appropriate HTTP response:
 * - HTML (with data-page attribute) on first visit
 * - JSON (page object) on Inertia XHR visits
 *
 * TODO: Inertia v3 — data-page attribute → <script type="application/json"> tag
 */
final class InertiaResponse
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $rootView,
    ) {
    }

    /**
     * Build and return an HTML or JSON response based on the request type.
     *
     * @param array<string, mixed> $props
     */
    public function build(
        string $component,
        array $props,
        string $url,
        ?string $version,
        Request $request,
        bool $clearHistory = false,
        bool $encryptHistory = false,
    ): Response {
        // TODO: implement
    }

    /**
     * Filter and resolve prop values according to partial reload headers.
     * - LazyProp: skipped on full render, resolved on partial if requested
     * - DeferProp: always excluded (goes into deferredProps)
     * - OnceProp: resolved unless key is in X-Inertia-Except-Once-Props
     * - MergeProp: resolved and tracked in mergeProps/prependProps/deepMergeProps
     *
     * @param array<string, mixed> $props
     * @param string[]             $only   props to include (CSV from X-Inertia-Partial-Data)
     * @param string[]             $except props to exclude (CSV from X-Inertia-Partial-Except)
     * @param string[]             $exceptOnce props to skip (CSV from X-Inertia-Except-Once-Props)
     * @param bool                 $isPartial whether this is a partial reload request
     * @return array<string, mixed>
     */
    public function resolveProps(
        array $props,
        array $only = [],
        array $except = [],
        array $exceptOnce = [],
        bool $isPartial = false,
    ): array {
        // TODO: implement
    }

    /**
     * Build the canonical v2 page object array.
     * clearHistory and encryptHistory are ALWAYS present in v2, even if false.
     *
     * TODO: Inertia v3 — clearHistory/encryptHistory omitted if false
     *
     * @param array<string, mixed>            $resolvedProps
     * @param array<string, list<string>>     $deferredProps   grouped deferred prop keys
     * @param list<string>                    $mergeProps
     * @param list<string>                    $prependProps
     * @param list<string>                    $deepMergeProps
     * @return array<string, mixed>
     */
    public function buildPageObject(
        string $component,
        array $resolvedProps,
        string $url,
        ?string $version,
        bool $clearHistory,
        bool $encryptHistory,
        array $deferredProps = [],
        array $mergeProps = [],
        array $prependProps = [],
        array $deepMergeProps = [],
    ): array {
        // TODO: implement
    }

    /**
     * @param string[] $csv
     * @return string[]
     */
    private function parseCsv(string $header): array
    {
        if ('' === $header) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $header))));
    }
}
