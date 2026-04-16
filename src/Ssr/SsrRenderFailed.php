<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Ssr;

/**
 * Event dispatched when the SSR server fails to render a component.
 *
 * Listeners can subscribe to this event to log errors, send alerts, or apply
 * custom fallback strategies. When ssr_throw_on_error is true, the gateway
 * will also throw after dispatching this event.
 *
 * Usage:
 *   $dispatcher->addListener(SsrRenderFailed::class, function (SsrRenderFailed $event) {
 *       // log, alert, etc.
 *   });
 */
final class SsrRenderFailed
{
    /**
     * @param array<string, mixed> $page           The Inertia page object being rendered
     * @param string               $error          Error message from the SSR server
     * @param SsrErrorType         $type           Classified error type
     * @param string|null          $hint           Actionable hint for fixing the error
     * @param string|null          $browserApi     Browser API accessed (when type is BrowserApi)
     * @param string|null          $stack          Stack trace from the SSR server
     * @param string|null          $sourceLocation File:line:column where the error occurred
     */
    public function __construct(
        public readonly array $page,
        public readonly string $error,
        public readonly SsrErrorType $type = SsrErrorType::Unknown,
        public readonly ?string $hint = null,
        public readonly ?string $browserApi = null,
        public readonly ?string $stack = null,
        public readonly ?string $sourceLocation = null,
    ) {
    }

    public function component(): string
    {
        return isset($this->page['component']) && \is_string($this->page['component'])
            ? $this->page['component']
            : 'Unknown';
    }

    public function url(): string
    {
        return isset($this->page['url']) && \is_string($this->page['url'])
            ? $this->page['url']
            : '/';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'component' => $this->component(),
            'url' => $this->url(),
            'error' => $this->error,
            'type' => $this->type->value,
            'hint' => $this->hint,
            'browser_api' => $this->browserApi,
            'stack' => $this->stack,
            'source_location' => $this->sourceLocation,
        ], static fn (mixed $v): bool => null !== $v);
    }
}
