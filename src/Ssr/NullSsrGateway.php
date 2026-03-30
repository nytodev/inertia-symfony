<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Ssr;

/**
 * Null object — used when SSR is disabled.
 * Always returns null so rendering falls back to client-side.
 */
final class NullSsrGateway implements SsrGatewayInterface
{
    public function dispatch(array $page): ?SsrResponse
    {
        return null;
    }
}
