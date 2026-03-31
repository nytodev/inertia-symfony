<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Props;

/**
 * A merge prop that carries pagination metadata for client-side scroll restoration.
 * Appears in mergeProps (or prependProps) and populates the scrollProps page object field.
 *
 * Supports optional deferred loading: when defer() is called the prop is excluded from
 * the initial page object and fetched by the client in a separate XHR request (like DeferProp),
 * while still appearing in mergeProps and deferredProps on the initial render.
 */
final class ScrollProp
{
    private bool $deferred = false;
    private string $deferGroup = 'default';

    public function __construct(
        private readonly \Closure $callback,
        private readonly string $pageName = 'page',
        private readonly int|string|null $nextPage = null,
        private readonly int|string|null $previousPage = null,
        private readonly int|string|null $currentPage = null,
        private readonly bool $prepend = false,
    ) {
    }

    public function defer(string $group = 'default'): static
    {
        $this->deferred = true;
        $this->deferGroup = $group;

        return $this;
    }

    public function shouldDefer(): bool
    {
        return $this->deferred;
    }

    public function getGroup(): string
    {
        return $this->deferGroup;
    }

    public function resolve(): mixed
    {
        return ($this->callback)();
    }

    public function getPageName(): string
    {
        return $this->pageName;
    }

    public function getNextPage(): int|string|null
    {
        return $this->nextPage;
    }

    public function getPreviousPage(): int|string|null
    {
        return $this->previousPage;
    }

    public function getCurrentPage(): int|string|null
    {
        return $this->currentPage;
    }

    public function isPrepend(): bool
    {
        return $this->prepend;
    }

    /**
     * Build the scrollProps metadata entry for this prop.
     * All pagination fields are always present (null when not set), matching the Inertia.js v2 protocol type.
     *
     * @return array<string, int|string|null>
     */
    public function toMetadata(): array
    {
        return [
            'pageName' => $this->pageName,
            'previousPage' => $this->previousPage,
            'nextPage' => $this->nextPage,
            'currentPage' => $this->currentPage,
        ];
    }
}
