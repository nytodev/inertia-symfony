<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Twig;

use Nytodev\InertiaBundle\Ssr\SsrState;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for rendering Inertia root elements:
 *   {{ inertia(page) }}      → SSR body, or <script type="application/json"> + <div id="app"> as fallback
 *   {{ inertiaHead(page) }}  → SSR head content (empty string when SSR disabled)
 *
 * Delegates dispatch caching to SsrState (tagged kernel.reset) so the gateway
 * is called at most once per request even when both functions are invoked.
 */
final class InertiaTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly SsrState $ssrState,
    ) {
    }

    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'inertia',
                [$this, 'renderInertia'],
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'inertiaHead',
                [$this, 'renderInertiaHead'],
                ['is_safe' => ['html']],
            ),
        ];
    }

    /**
     * Renders the Inertia mount point.
     * Returns the SSR-rendered body when available,
     * or the v3 format <script type="application/json"> + <div id="app"> as fallback.
     *
     * @param array<string, mixed> $page
     */
    public function renderInertia(array $page): string
    {
        $this->ssrState->setPage($page);
        $response = $this->ssrState->dispatch();

        if (null !== $response) {
            return $response->body;
        }

        $json = json_encode($page, \JSON_HEX_TAG | \JSON_THROW_ON_ERROR);

        return '<script data-page="app" type="application/json">'.$json.'</script>'."\n".'<div id="app"></div>';
    }

    /**
     * Renders SSR head content injected by the Node.js SSR server.
     * Returns an empty string when SSR is disabled or the server is unreachable.
     *
     * @param array<string, mixed> $page
     */
    public function renderInertiaHead(array $page): string
    {
        $this->ssrState->setPage($page);
        $response = $this->ssrState->dispatch();

        return null !== $response ? $response->head : '';
    }
}
