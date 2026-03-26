<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Props;

/**
 * A prop that is resolved once and skipped on subsequent requests
 * if its key appears in the X-Inertia-Except-Once-Props header.
 */
final class OnceProp
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
