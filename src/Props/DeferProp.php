<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Props;

/**
 * A prop that is excluded from the initial response and loaded by the client
 * via a separate XHR request. Props are grouped by name for batching.
 *
 * Supports optional merge semantics: when merge(), deepMerge(), or prepend() is called,
 * the key appears in mergeProps/deepMergeProps/prependProps on the initial page object
 * so the client knows to merge (not replace) when the deferred XHR resolves.
 */
final class DeferProp
{
    private bool $merge = false;
    private bool $deep = false;
    private bool $prepend = false;
    /** @var string[] */
    private array $matchOn = [];

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

    public function merge(): static
    {
        $this->merge = true;

        return $this;
    }

    public function deepMerge(): static
    {
        $this->deep = true;
        $this->merge = true;

        return $this;
    }

    public function prepend(): static
    {
        $this->prepend = true;
        $this->merge = true;

        return $this;
    }

    /**
     * @param string|string[] $keys
     */
    public function matchOn(string|array $keys): static
    {
        $this->matchOn = \is_string($keys) ? [$keys] : array_values($keys);

        return $this;
    }

    public function shouldMerge(): bool
    {
        return $this->merge;
    }

    public function isDeep(): bool
    {
        return $this->deep;
    }

    public function isPrepend(): bool
    {
        return $this->prepend;
    }

    /**
     * @return string[]
     */
    public function getMatchOn(): array
    {
        return $this->matchOn;
    }
}
