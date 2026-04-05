<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Props;

/**
 * A prop that is never evaluated on a full first render.
 * It is only resolved when explicitly requested via X-Inertia-Partial-Data.
 *
 * Calling once() adds OnceProp caching semantics: after the first explicit
 * partial resolve the client caches the value and sends X-Inertia-Except-Once-Props
 * on subsequent visits, so the server skips resolution entirely.
 */
final class LazyProp
{
    private bool $once = false;

    public function __construct(
        private readonly \Closure $callback,
    ) {
    }

    public function resolve(): mixed
    {
        return ($this->callback)();
    }

    public function once(): static
    {
        $this->once = true;

        return $this;
    }

    public function isOnce(): bool
    {
        return $this->once;
    }
}
