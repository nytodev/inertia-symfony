<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Props;

/**
 * A prop that is excluded from the initial response and loaded by the client
 * via a separate XHR request. Props are grouped by name for batching.
 */
final class DeferProp
{
    public function __construct(
        private readonly \Closure $callback,
        private readonly string $group = 'default',
    ) {
    }

    public function resolve(): mixed
    {
        return ($this->callback)();
    }

    public function getGroup(): string
    {
        return $this->group;
    }
}
