<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Props;

/**
 * A prop whose resolved value is merged with the client-side existing value.
 * Appears in mergeProps, prependProps, or deepMergeProps depending on flags.
 */
final class MergeProp
{
    public function __construct(
        private readonly \Closure $callback,
        private readonly bool $prepend = false,
        private readonly bool $deep = false,
        private readonly ?string $matchOn = null,
    ) {
    }

    public function resolve(): mixed
    {
        return ($this->callback)();
    }

    public function isPrepend(): bool
    {
        return $this->prepend;
    }

    public function isDeep(): bool
    {
        return $this->deep;
    }

    public function getMatchOn(): ?string
    {
        return $this->matchOn;
    }
}
