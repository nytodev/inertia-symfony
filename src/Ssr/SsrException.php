<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Ssr;

/**
 * Thrown when the SSR server fails and ssr_throw_on_error is enabled.
 * Carries the full SsrRenderFailed event for structured error handling.
 */
final class SsrException extends \RuntimeException
{
    private function __construct(
        string $message,
        private readonly SsrRenderFailed $event,
    ) {
        parent::__construct($message);
    }

    public static function fromEvent(SsrRenderFailed $event): self
    {
        $message = \sprintf(
            'SSR render failed for component [%s]: %s',
            $event->component(),
            $event->error,
        );

        if (null !== $event->sourceLocation) {
            $message .= \sprintf(' at %s', $event->sourceLocation);
        }

        return new self($message, $event);
    }

    public function component(): string
    {
        return $this->event->component();
    }

    public function type(): SsrErrorType
    {
        return $this->event->type;
    }

    public function hint(): ?string
    {
        return $this->event->hint;
    }

    public function sourceLocation(): ?string
    {
        return $this->event->sourceLocation;
    }
}
