<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Response;

use Nytodev\InertiaBundle\Props\AlwaysProp;
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
        $reset = $this->parseCsv($request->headers->get('X-Inertia-Reset') ?? '');
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

        $resolved = $this->resolveProps($props, $only, $except, $exceptOnce, $isPartial);

        // Guarantee: errors must always be present, even if a DeferProp/LazyProp was passed as 'errors'.
        if (!\array_key_exists('errors', $resolved)) {
            $resolved['errors'] = [];
        }

        // BUG 3 fix: collect merge arrays AFTER resolving+filtering so excluded keys are absent.
        [$mergeProps, $prependProps, $deepMergeProps, $matchPropsOn] = $this->collectMergeArrays($props, $resolved);

        // Collect onceProps metadata for keys that survived into resolved props.
        $oncePropsMeta = [];
        foreach ($props as $key => $prop) {
            if ($prop instanceof OnceProp && \array_key_exists($key, $resolved)) {
                $expiresAt = $prop->getExpiresAt();
                $meta = [
                    'prop' => $prop->getAlias() ?? $key,
                    'expiresAt' => null !== $expiresAt ? $expiresAt->format(\DateTimeInterface::ATOM) : null,
                ];
                if ($prop->isFresh()) {
                    $meta['fresh'] = true;
                }
                $oncePropsMeta[$key] = $meta;
            }
        }

        $page = $this->buildPageObject(
            $component,
            $resolved,
            $url,
            $version,
            $clearHistory,
            $encryptHistory,
            $isPartial ? [] : $deferredGroups,
            $mergeProps,
            $prependProps,
            $deepMergeProps,
            $isPartial ? $reset : [],
            $oncePropsMeta,
            $matchPropsOn,
        );

        if ($request->headers->has('X-Inertia')) {
            return new JsonResponse($page, 200, [
                'X-Inertia' => 'true',
                'Vary' => 'X-Inertia',
            ]);
        }

        $html = $this->twig->render($this->rootView, ['page' => $page]);

        return new Response($html, 200, ['Vary' => 'X-Inertia']);
    }

    /**
     * Filter and resolve prop values according to partial reload headers.
     * - LazyProp: skipped on full render, resolved on partial ONLY if explicitly in $only
     * - DeferProp: always excluded (goes into deferredProps)
     * - OnceProp: resolved unless key is in X-Inertia-Except-Once-Props or in $except
     * - MergeProp: resolved (merge metadata is collected separately in build()).
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
        /** @var string[] $alwaysKeys */
        $alwaysKeys = [];

        foreach ($props as $key => $value) {
            // DeferProp: excluded on full render and on partial when key is not explicitly in $only.
            // On a deferred-fetch partial reload (key in $only), resolve and include.
            if ($value instanceof DeferProp) {
                if (!$isPartial || [] === $only || !\in_array($key, $only, true)) {
                    continue;
                }
                $resolved[$key] = $value->resolve();
                continue;
            }

            // AlwaysProp: resolved on every render; bypasses partial $only/$except filters.
            if ($value instanceof AlwaysProp) {
                $resolved[$key] = $value->resolve();
                $alwaysKeys[] = $key;
                continue;
            }

            // LazyProp: skip on full render; on partial, include only if explicitly in $only.
            // BUG 1 fix: check $except before resolving.
            // BUG 4 fix: require [] !== $only — if $only is empty, LazyProp is never resolved.
            if ($value instanceof LazyProp) {
                if (!$isPartial || [] === $only || !\in_array($key, $only, true)) {
                    continue;
                }
                // $except wins: do not resolve if key is in $except.
                if ([] !== $except && \in_array($key, $except, true)) {
                    continue;
                }
                $resolved[$key] = $value->resolve();
                continue;
            }

            // OnceProp: skip if key is in $exceptOnce.
            // Also skip (without resolving) if key is in $except or not in $only during partial reload.
            if ($value instanceof OnceProp) {
                if (\in_array($key, $exceptOnce, true)) {
                    continue;
                }
                if ($isPartial && [] !== $except && \in_array($key, $except, true)) {
                    continue;
                }
                if ($isPartial && [] !== $only && !\in_array($key, $only, true)) {
                    continue;
                }
                $resolved[$key] = $value->resolve();
                continue;
            }

            // MergeProp: resolve the value, but skip (without resolving) if partial-reload filters exclude it.
            if ($value instanceof MergeProp) {
                if ($isPartial && [] !== $except && \in_array($key, $except, true)) {
                    continue;
                }
                if ($isPartial && [] !== $only && !\in_array($key, $only, true)) {
                    continue;
                }
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
            // errors and AlwaysProp keys are always included regardless of partial filters.
            if ('errors' === $key || \in_array($key, $alwaysKeys, true)) {
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
     * Collect merge/prepend/deepMerge/matchPropsOn arrays from the original props,
     * restricted to keys that survived into the final resolved props (i.e. not filtered out).
     *
     * @param array<string, mixed> $rawProps      original props before resolution
     * @param array<string, mixed> $resolvedProps props after resolution and filtering
     *
     * @return array{0: list<string>, 1: list<string>, 2: list<string>, 3: list<string>}
     */
    private function collectMergeArrays(array $rawProps, array $resolvedProps): array
    {
        $mergeProps = [];
        $prependProps = [];
        $deepMergeProps = [];
        $matchPropsOn = [];

        foreach ($rawProps as $key => $prop) {
            if (!$prop instanceof MergeProp) {
                continue;
            }
            // Only include keys that survived the filter.
            if (!\array_key_exists($key, $resolvedProps)) {
                continue;
            }
            if ($prop->isDeep()) {
                $deepMergeProps[] = $key;
            } elseif ($prop->isPrepend()) {
                $prependProps[] = $key;
            } else {
                $mergeProps[] = $key;
            }
            foreach ($prop->getMatchOn() as $field) {
                $matchPropsOn[] = $key.'.'.$field;
            }
        }

        return [$mergeProps, $prependProps, $deepMergeProps, $matchPropsOn];
    }

    /**
     * Build the canonical v2 page object array.
     * clearHistory and encryptHistory are ALWAYS present in v2, even if false.
     *
     * TODO: Inertia v3 — clearHistory/encryptHistory omitted if false
     *
     * @param array<string, mixed>                                 $resolvedProps
     * @param array<string, list<string>>                          $deferredProps  grouped deferred prop keys
     * @param list<string>                                         $mergeProps
     * @param list<string>                                         $prependProps
     * @param list<string>                                         $deepMergeProps
     * @param list<string>                                         $resetProps     keys to reset before merging
     * @param array<string, array{prop: string, expiresAt: mixed}> $onceProps      once-prop metadata
     * @param list<string>                                         $matchPropsOn   "propKey.fieldKey" entries for dedup
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
        array $resetProps = [],
        array $onceProps = [],
        array $matchPropsOn = [],
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

        if ([] !== $resetProps) {
            $page['resetProps'] = $resetProps;
        }

        if ([] !== $onceProps) {
            $page['onceProps'] = $onceProps;
        }

        if ([] !== $matchPropsOn) {
            $page['matchPropsOn'] = $matchPropsOn;
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
