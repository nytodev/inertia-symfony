<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Props;

/**
 * A prop that is never evaluated on a full first render.
 * It is only resolved when explicitly requested via X-Inertia-Partial-Data.
 */
final class LazyProp
{
    public function __construct(
        private readonly \Closure $callback,
    ) {
    }

    public function resolve(): mixed
    {
        return ($this->callback)();
    }
}
