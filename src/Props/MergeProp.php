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
    /** @var string[] */
    private readonly array $appendsAtPaths;
    /** @var string[] */
    private readonly array $prependsAtPaths;

    /**
     * @param string|string[] $matchOn         one or more field names used for client-side deduplication
     * @param string|string[] $appendsAtPaths  sub-paths to merge instead of the root prop (e.g. 'data' → 'posts.data')
     * @param string|string[] $prependsAtPaths sub-paths to prepend instead of the root prop
     */
    public function __construct(
        private readonly \Closure $callback,
        private readonly bool $prepend = false,
        private readonly bool $deep = false,
        string|array $matchOn = [],
        string|array $appendsAtPaths = [],
        string|array $prependsAtPaths = [],
    ) {
        $this->matchOn = \is_string($matchOn) ? [$matchOn] : array_values($matchOn);
        $this->appendsAtPaths = \is_string($appendsAtPaths) ? [$appendsAtPaths] : array_values($appendsAtPaths);
        $this->prependsAtPaths = \is_string($prependsAtPaths) ? [$prependsAtPaths] : array_values($prependsAtPaths);
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

    /**
     * @return string[]
     */
    public function getAppendsAtPaths(): array
    {
        return $this->appendsAtPaths;
    }

    /**
     * @return string[]
     */
    public function getPrependsAtPaths(): array
    {
        return $this->prependsAtPaths;
    }

    /**
     * Returns true when no path-specific arrays are set — merge targets the root prop key.
     */
    public function mergesAtRoot(): bool
    {
        return [] === $this->appendsAtPaths && [] === $this->prependsAtPaths;
    }
}
