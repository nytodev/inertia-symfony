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
        $only = $this->parseCsv($request->headers->get('X-Inertia-Partial-Data') ?? '');
        $except = $this->parseCsv($request->headers->get('X-Inertia-Partial-Except') ?? '');
        $exceptOnce = $this->parseCsv($request->headers->get('X-Inertia-Except-Once-Props') ?? '');
        $partialComponent = $request->headers->get('X-Inertia-Partial-Component') ?? '';

        $isPartial = ([] !== $only || [] !== $except) && $partialComponent === $component;

        if (!\array_key_exists('errors', $props)) {
            $props['errors'] = [];
        }

        // Collect deferred prop groups before resolving.
        $deferredGroups = [];
        foreach ($props as $key => $prop) {
            if ($prop instanceof DeferProp) {
                $group = $prop->getGroup();
                $deferredGroups[$group][] = $key;
            }
        }

        // Collect merge prop keys before resolving.
        $mergeProps = [];
        $prependProps = [];
        $deepMergeProps = [];
        foreach ($props as $key => $prop) {
            if (!$prop instanceof MergeProp) {
                continue;
            }
            if ($prop->isDeep()) {
                $deepMergeProps[] = $key;
            } elseif ($prop->isPrepend()) {
                $prependProps[] = $key;
            } else {
                $mergeProps[] = $key;
            }
        }

        $resolved = $this->resolveProps($props, $only, $except, $exceptOnce, $isPartial);

        $page = $this->buildPageObject(
            $component,
            $resolved,
            $url,
            $version,
            $clearHistory,
            $encryptHistory,
            $deferredGroups,
            $mergeProps,
            $prependProps,
            $deepMergeProps,
        );

        if ($request->headers->has('X-Inertia')) {
            return new JsonResponse($page, 200, [
                'X-Inertia' => 'true',
                'Vary' => 'X-Inertia',
            ]);
        }

        $html = $this->twig->render($this->rootView, ['page' => $page]);

        return new Response($html);
    }

    /**
     * Filter and resolve prop values according to partial reload headers.
     * - LazyProp: skipped on full render, resolved on partial if requested
     * - DeferProp: always excluded (goes into deferredProps)
     * - OnceProp: resolved unless key is in X-Inertia-Except-Once-Props
     * - MergeProp: resolved and tracked in mergeProps/prependProps/deepMergeProps.
     *
     * @param array<string, mixed> $props
     * @param string[]             $only       props to include (CSV from X-Inertia-Partial-Data)
     * @param string[]             $except     props to exclude (CSV from X-Inertia-Partial-Except)
     * @param string[]             $exceptOnce props to skip (CSV from X-Inertia-Except-Once-Props)
     * @param bool                 $isPartial  whether this is a partial reload request
     *
     * @return array<string, mixed>
     */
    public function resolveProps(
        array $props,
        array $only = [],
        array $except = [],
        array $exceptOnce = [],
        bool $isPartial = false,
    ): array {
        $resolved = [];

        foreach ($props as $key => $value) {
            // DeferProp is always excluded from the props array.
            if ($value instanceof DeferProp) {
                continue;
            }

            // LazyProp: skip on full render; on partial, include only if in $only.
            if ($value instanceof LazyProp) {
                if (!$isPartial) {
                    continue;
                }
                // In partial reload, LazyProp is resolved only if key is in $only.
                if ([] !== $only && !\in_array($key, $only, true)) {
                    continue;
                }
                $resolved[$key] = $value->resolve();
                continue;
            }

            // OnceProp: skip if key is in $exceptOnce, otherwise resolve.
            if ($value instanceof OnceProp) {
                if (\in_array($key, $exceptOnce, true)) {
                    continue;
                }
                $resolved[$key] = $value->resolve();
                continue;
            }

            // MergeProp: resolve the value.
            if ($value instanceof MergeProp) {
                $resolved[$key] = $value->resolve();
                continue;
            }

            // Closures: resolve.
            if ($value instanceof \Closure) {
                $resolved[$key] = $value();
                continue;
            }

            $resolved[$key] = $value;
        }

        if (!$isPartial) {
            return $resolved;
        }

        // Partial reload: apply $only/$except filtering. $except wins when both are set.
        $filtered = [];
        foreach ($resolved as $key => $value) {
            // errors is always included regardless of partial filters.
            if ('errors' === $key) {
                $filtered[$key] = $value;
                continue;
            }

            // $except wins: skip any key in the except list.
            if ([] !== $except && \in_array($key, $except, true)) {
                continue;
            }

            // If $only is set and key is not in it, skip.
            if ([] !== $only && !\in_array($key, $only, true)) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

    /**
     * Build the canonical v2 page object array.
     * clearHistory and encryptHistory are ALWAYS present in v2, even if false.
     *
     * TODO: Inertia v3 — clearHistory/encryptHistory omitted if false
     *
     * @param array<string, mixed>        $resolvedProps
     * @param array<string, list<string>> $deferredProps  grouped deferred prop keys
     * @param list<string>                $mergeProps
     * @param list<string>                $prependProps
     * @param list<string>                $deepMergeProps
     *
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
        $page = [
            'component' => $component,
            'props' => $resolvedProps,
            'url' => $url,
            'version' => $version,
            'clearHistory' => $clearHistory,      // TODO: Inertia v3 — omit if false
            'encryptHistory' => $encryptHistory,  // TODO: Inertia v3 — omit if false
        ];

        if ([] !== $deferredProps) {
            $page['deferredProps'] = $deferredProps;
        }

        if ([] !== $mergeProps) {
            $page['mergeProps'] = $mergeProps;
        }

        if ([] !== $prependProps) {
            $page['prependProps'] = $prependProps;
        }

        if ([] !== $deepMergeProps) {
            $page['deepMergeProps'] = $deepMergeProps;
        }

        return $page;
    }

    /**
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
