<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Twig;

use Nytodev\InertiaBundle\Ssr\SsrGatewayInterface;
use Nytodev\InertiaBundle\Ssr\SsrResponse;
use Symfony\Contracts\Service\ResetInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions for rendering Inertia root elements:
 *   {{ inertia(page) }}      → SSR body, or <script type="application/json"> + <div id="app"> as fallback
 *   {{ inertiaHead(page) }}  → SSR head content (empty string when SSR disabled)
 *
 * The SSR gateway is called at most once per request; the result is cached until reset().
 * reset() is called automatically between requests in FrankenPHP/ReactPHP workers
 * via the kernel.reset container tag.
 */
final class InertiaTwigExtension extends AbstractExtension implements ResetInterface
{
    private bool $ssrDispatched = false;

    private ?SsrResponse $ssrResponse = null;

    public function __construct(
        private readonly SsrGatewayInterface $ssrGateway,
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
        $this->dispatchOnce($page);

        if (null !== $this->ssrResponse) {
            return $this->ssrResponse->body;
        }

        $json = json_encode(
            $page,
            \JSON_HEX_TAG | \JSON_HEX_APOS | \JSON_HEX_AMP | \JSON_HEX_QUOT | \JSON_THROW_ON_ERROR,
        );

        return '<script data-page="app" type="application/json">'.$json.'</script><div id="app"></div>';
    }

    /**
     * Renders SSR head content injected by the Node.js SSR server.
     * Returns an empty string when SSR is disabled or the server is unreachable.
     *
     * @param array<string, mixed> $page
     */
    public function renderInertiaHead(array $page): string
    {
        $this->dispatchOnce($page);

        return null !== $this->ssrResponse ? $this->ssrResponse->head : '';
    }

    /**
     * Reset SSR state between requests (FrankenPHP / persistent workers).
     * Tagged with kernel.reset in services.yaml.
     */
    public function reset(): void
    {
        $this->ssrDispatched = false;
        $this->ssrResponse = null;
    }

    /**
     * @param array<string, mixed> $page
     */
    private function dispatchOnce(array $page): void
    {
        if ($this->ssrDispatched) {
            return;
        }

        $this->ssrDispatched = true;
        $this->ssrResponse = $this->ssrGateway->dispatch($page);
    }
}
