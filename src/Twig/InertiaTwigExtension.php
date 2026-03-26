<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for rendering Inertia root elements:
 *   {{ inertia(page) }}      → <div id="app" data-page="..."></div>
 *   {{ inertiaHead(page) }}  → SSR head content (empty string when SSR disabled)
 *
 * TODO: Inertia v3 — inertia() renders <script type="application/json"> instead of data-page attr
 */
final class InertiaTwigExtension extends AbstractExtension
{
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
     * Renders the root <div id="app" data-page='...'></div> element.
     * JSON is escaped with JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT.
     *
     * @param array<string, mixed> $page
     */
    public function renderInertia(array $page): string
    {
        // TODO: implement
    }

    /**
     * Renders SSR head content injected by the Node.js SSR server.
     * Returns an empty string when SSR is disabled.
     *
     * @param array<string, mixed> $page
     */
    public function renderInertiaHead(array $page): string
    {
        // TODO: implement
        return '';
    }
}
