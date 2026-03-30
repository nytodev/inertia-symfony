<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Props;

/**
 * A prop that is resolved once and skipped on subsequent requests
 * if its key appears in the X-Inertia-Except-Once-Props header.
 *
 * Supports optional modifiers:
 *   ->as(string $alias)        — cache the value under a different key client-side
 *   ->until(\DateTimeInterface|int $ttl) — set an expiry time (int = seconds from now)
 *   ->fresh()                  — force re-evaluation even if the client has it cached
 */
final class OnceProp
{
    private ?string $alias = null;
    private ?\DateTimeInterface $expiresAt = null;
    private bool $fresh = false;

    public function __construct(
        private readonly \Closure $callback,
    ) {
    }

    public function resolve(): mixed
    {
        return ($this->callback)();
    }

    public function as(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    public function until(\DateTimeInterface|int $ttl): static
    {
        $this->expiresAt = \is_int($ttl)
            ? new \DateTimeImmutable('@'.(time() + $ttl))
            : $ttl;

        return $this;
    }

    public function fresh(): static
    {
        $this->fresh = true;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function isFresh(): bool
    {
        return $this->fresh;
    }
}
