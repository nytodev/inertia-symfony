<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Ssr;

/**
 * Contract for dispatching an Inertia page object to the SSR server.
 * Returns null when SSR is disabled or the server is unreachable (fallback to CSR).
 */
interface SsrGatewayInterface
{
    /**
     * @param array<string, mixed> $page
     */
    public function dispatch(array $page): ?SsrResponse;
}
