<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Service;

use Nytodev\InertiaBundle\Props\AlwaysProp;
use Nytodev\InertiaBundle\Props\DeferProp;
use Nytodev\InertiaBundle\Props\LazyProp;
use Nytodev\InertiaBundle\Props\MergeProp;
use Nytodev\InertiaBundle\Props\OnceProp;
use Nytodev\InertiaBundle\Props\ScrollProp;
use Nytodev\InertiaBundle\Response\InertiaResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Main Inertia service. Manages shared props and delegates response building
 * to InertiaResponse.
 */
final class Inertia implements ResetInterface
{
    /** @var array<string, mixed> */
    private array $sharedProps = [];

    /**
     * In-memory fallback for flash data when no session is available.
     *
     * @var array<string, mixed>
     */
    private array $flashData = [];

    /**
     * In-memory fallback for errors data when no session is available.
     * Keyed by bag name (e.g. 'default', 'login').
     *
     * @var array<string, array<string, mixed>>
     */
    private array $errorsData = [];

    private bool $clearHistory = false;

    private bool $encryptHistory = false;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly InertiaResponse $inertiaResponse,
        private readonly ?string $version,
        private readonly bool $defaultEncryptHistory = false,
    ) {
        $this->encryptHistory = $defaultEncryptHistory;
    }

    /**
     * Render an Inertia component. Merges shared props with the given props
     * and returns either an HTML or JSON response based on the request headers.
     *
     * @param array<string, mixed> $props
     */
    public function render(string $component, array $props = []): Response
    {
        $request = $this->requestStack->getCurrentRequest()
            ?? throw new \LogicException('No current request.');

        $mergedProps = array_merge($this->sharedProps, $props);

        $clearHistory = $this->clearHistory;
        $encryptHistory = $this->encryptHistory;
        $this->clearHistory = false;
        $this->encryptHistory = false;

        $flash = $this->consumeFlash($request);

        // Inject errors only when the caller has not already provided an 'errors' key.
        if (!\array_key_exists('errors', $mergedProps)) {
            $mergedProps['errors'] = $this->consumeErrors($request);
        } else {
            // Discard pending errors so they do not leak to the next request.
            $this->consumeErrors($request);
        }

        $qs = $request->getQueryString();

        return $this->inertiaResponse->build(
            $component,
            $mergedProps,
            $request->getBaseUrl().$request->getPathInfo().(null !== $qs ? '?'.$qs : ''),
            $this->version,
            $request,
            $clearHistory,
            $encryptHistory,
            $flash,
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
     * Share a prop that is sent once and cached by the client.
     * The value is wrapped in an OnceProp and stored in $sharedProps.
     * The client controls re-sending via X-Inertia-Except-Once-Props.
     */
    public function shareOnce(string $key, mixed $value): void
    {
        $this->sharedProps[$key] = $value instanceof OnceProp
            ? $value
            : new OnceProp($value instanceof \Closure ? $value : static fn () => $value);
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
     * Flash a key/value pair into the page object for the next Inertia render.
     *
     * When a session is available, the value is stored in the session under
     * '_inertia_flash' so it survives redirects (e.g. POST → 303 → GET).
     * When no session is available (stateless routes), the value is kept in
     * memory and is available only within the same request lifecycle.
     *
     * NOTE: Flash values must be serializable (same constraint as any session data).
     */
    public function flash(string $key, mixed $value): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request && $request->hasSession()) {
            $session = $request->getSession();
            /** @var array<string, mixed> $existing */
            $existing = $session->get('_inertia_flash', []);
            $existing[$key] = $value;
            $session->set('_inertia_flash', $existing);

            return;
        }

        $this->flashData[$key] = $value;
    }

    /**
     * Reset service state between requests (FrankenPHP / ReactPHP persistent workers).
     * Called automatically by the container via the kernel.reset tag.
     */
    public function reset(): void
    {
        $this->sharedProps = [];
        $this->flashData = [];
        $this->errorsData = [];
        $this->clearHistory = false;
        $this->encryptHistory = $this->defaultEncryptHistory;
    }

    /**
     * Read and clear all pending flash data (session + in-memory fallback).
     * Called exactly once per render() invocation.
     *
     * @return array<string, mixed>
     */
    private function consumeFlash(\Symfony\Component\HttpFoundation\Request $request): array
    {
        $flash = $this->flashData;
        $this->flashData = [];

        if ($request->hasSession()) {
            $session = $request->getSession();
            /** @var array<string, mixed> $sessionFlash */
            $sessionFlash = $session->get('_inertia_flash', []);
            $session->remove('_inertia_flash');
            $flash = array_merge($sessionFlash, $flash);
        }

        return $flash;
    }

    /**
     * Store validation errors to be auto-injected into the next Inertia render.
     *
     * When a session is available, the errors are persisted under '_inertia_errors'
     * so they survive redirects (PRG pattern: PUT → 303 → GET).
     * When no session is available (stateless routes), errors are kept in memory
     * and are available only within the same request lifecycle.
     *
     * Multiple calls merge errors within each bag rather than overwriting.
     *
     * @param array<string, mixed> $errors
     */
    public function errors(array $errors, string $bag = 'default'): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null !== $request && $request->hasSession()) {
            $session = $request->getSession();
            /** @var array<string, array<string, mixed>> $existing */
            $existing = $session->get('_inertia_errors', []);
            $existing[$bag] = array_merge($existing[$bag] ?? [], $errors);
            $session->set('_inertia_errors', $existing);

            return;
        }

        $this->errorsData[$bag] = array_merge($this->errorsData[$bag] ?? [], $errors);
    }

    /**
     * Read and clear all pending errors (session + in-memory fallback).
     * Applies X-Inertia-Error-Bag header logic (identical to Laravel resolveValidationErrors()):
     * - Only 'default' bag + header present → wrap under header value
     * - Only 'default' bag + no header → return flat array
     * - Multiple bags → return keyed by bag name.
     *
     * Called exactly once per render() invocation.
     *
     * @return array<string, mixed>
     */
    private function consumeErrors(\Symfony\Component\HttpFoundation\Request $request): array
    {
        /** @var array<string, array<string, mixed>> $bags */
        $bags = $this->errorsData;
        $this->errorsData = [];

        if ($request->hasSession()) {
            $session = $request->getSession();
            /** @var array<string, array<string, mixed>> $sessionErrors */
            $sessionErrors = $session->get('_inertia_errors', []);
            $session->remove('_inertia_errors');
            foreach ($sessionErrors as $bagName => $bagErrors) {
                $bags[$bagName] = array_merge($bags[$bagName] ?? [], $bagErrors);
            }
        }

        if ([] === $bags) {
            return [];
        }

        $bagNames = array_keys($bags);
        $isOnlyDefault = ['default'] === $bagNames;

        if ($isOnlyDefault) {
            $errorBagHeader = $request->headers->get('X-Inertia-Error-Bag');
            if (null !== $errorBagHeader && '' !== $errorBagHeader) {
                return [$errorBagHeader => $bags['default']];
            }

            return $bags['default'];
        }

        return $bags;
    }

    /**
     * Signal that the browser history entry for this response should be cleared.
     * The flag is consumed on the next render() call and then reset to false.
     */
    public function clearHistory(): void
    {
        $this->clearHistory = true;
    }

    /**
     * Signal that the browser history entry for this response should be encrypted.
     * The flag is consumed on the next render() call and then reset to false.
     */
    public function encryptHistory(): void
    {
        $this->encryptHistory = true;
    }

    public function always(\Closure $callback): AlwaysProp
    {
        return new AlwaysProp($callback);
    }

    public function lazy(\Closure $callback): LazyProp
    {
        return new LazyProp($callback);
    }

    public function optional(\Closure $callback): LazyProp
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

    /**
     * @param string|string[] $matchOn         one or more field names for client-side deduplication
     * @param string|string[] $appendsAtPaths  sub-paths to merge instead of the root prop (e.g. 'data' → 'posts.data')
     * @param string|string[] $prependsAtPaths sub-paths to prepend instead of the root prop
     */
    public function merge(\Closure $callback, bool $prepend = false, bool $deep = false, string|array $matchOn = [], string|array $appendsAtPaths = [], string|array $prependsAtPaths = []): MergeProp
    {
        return new MergeProp($callback, $prepend, $deep, $matchOn, $appendsAtPaths, $prependsAtPaths);
    }

    /**
     * Shorthand for merge() with deep=true.
     *
     * @param string|string[] $matchOn         one or more field names for client-side deduplication
     * @param string|string[] $appendsAtPaths  sub-paths to deep-merge instead of the root prop
     * @param string|string[] $prependsAtPaths sub-paths to prepend instead of the root prop
     */
    public function deepMerge(\Closure $callback, string|array $matchOn = [], string|array $appendsAtPaths = [], string|array $prependsAtPaths = []): MergeProp
    {
        return new MergeProp($callback, false, true, $matchOn, $appendsAtPaths, $prependsAtPaths);
    }

    /**
     * Force a full browser navigation to the given URL, bypassing the SPA.
     *
     * For Inertia XHR requests: returns 409 Conflict + X-Inertia-Location header.
     * For first-visit (non-XHR) requests: returns a standard 302 redirect.
     */
    public function location(string $url): Response
    {
        $request = $this->requestStack->getCurrentRequest()
            ?? throw new \LogicException('No current request.');

        if ($request->headers->has('X-Inertia')) {
            return new Response('', 409, ['X-Inertia-Location' => $url]);
        }

        return new RedirectResponse($url, 302);
    }

    public function scroll(
        \Closure $callback,
        string $pageName = 'page',
        int|string|null $nextPage = null,
        int|string|null $previousPage = null,
        int|string|null $currentPage = null,
        bool $prepend = false,
    ): ScrollProp {
        return new ScrollProp($callback, $pageName, $nextPage, $previousPage, $currentPage, $prepend);
    }
}
