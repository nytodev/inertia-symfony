<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Exception;

use Symfony\Component\HttpFoundation\Request;

/**
 * DTO passed to the handleExceptionsUsing() callback.
 * Call render() to replace the error page with an Inertia component.
 */
final class ExceptionResponse
{
    private ?string $component = null;

    /** @var array<string, mixed> */
    private array $props = [];

    public function __construct(
        public readonly \Throwable $exception,
        public readonly Request $request,
        private readonly int $httpStatus,
    ) {
    }

    /**
     * @param array<string, mixed> $props
     */
    public function render(string $component, array $props = []): self
    {
        $this->component = $component;
        $this->props = $props;

        return $this;
    }

    public function statusCode(): int
    {
        return $this->httpStatus;
    }

    public function hasComponent(): bool
    {
        return null !== $this->component;
    }

    public function getComponent(): string
    {
        if (null === $this->component) {
            throw new \LogicException('No component set. Call render() first.');
        }

        return $this->component;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProps(): array
    {
        return $this->props;
    }
}
