<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Props;

/**
 * A prop that is always resolved and always included in the response,
 * even during partial reloads — regardless of X-Inertia-Partial-Data or
 * X-Inertia-Partial-Except headers.
 */
final class AlwaysProp
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
