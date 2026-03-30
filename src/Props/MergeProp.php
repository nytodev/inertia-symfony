<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Props;

/**
 * A prop whose resolved value is merged with the client-side existing value.
 * Appears in mergeProps, prependProps, or deepMergeProps depending on flags.
 */
final class MergeProp
{
    /** @var string[] */
    private readonly array $matchOn;

    /**
     * @param string|string[] $matchOn one or more field names used for client-side deduplication
     */
    public function __construct(
        private readonly \Closure $callback,
        private readonly bool $prepend = false,
        private readonly bool $deep = false,
        string|array $matchOn = [],
    ) {
        $this->matchOn = \is_string($matchOn) ? [$matchOn] : array_values($matchOn);
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

    /**
     * @return string[]
     */
    public function getMatchOn(): array
    {
        return $this->matchOn;
    }
}
