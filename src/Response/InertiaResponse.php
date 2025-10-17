<?php

declare(strict_types=1);

namespace Nytodev\InertiaSymfony\Response;

use JsonSerializable;

/**
 * Immutable Data Transfer Object representing an Inertia.js page response.
 *
 * This class encapsulates all data needed to render an Inertia page,
 * including the component name, props, URL, and asset version.
 *
 * @see https://inertiajs.com/the-protocol
 */
final class InertiaResponse implements JsonSerializable
{
    /**
     * @param string $component The frontend component name (e.g., 'Dashboard/Index')
     * @param array<string, mixed> $props Data to pass to the component
     * @param string $url The current page URL
     * @param string $version Asset version for cache busting
     */
    public function __construct(
        private readonly string $component,
        private readonly array $props,
        private readonly string $url,
        private readonly string $version
    ) {
    }

    public function getComponent(): string
    {
        return $this->component;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProps(): array
    {
        return $this->props;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Create a new instance with additional props merged in.
     *
     * @param array<string, mixed> $additionalProps Props to merge with existing props
     * @param bool $replace If true, replace props entirely instead of merging
     * @return self New instance with merged or replaced props
     */
    public function withProps(array $additionalProps, bool $replace = false): self
    {
        return new self(
            $this->component,
            $replace ? $additionalProps : array_merge($this->props, $additionalProps),
            $this->url,
            $this->version
        );
    }

    /**
     * Create a new instance with a different component.
     *
     * @param string $component New component name
     * @return self New instance with new component
     */
    public function withComponent(string $component): self
    {
        return new self(
            $component,
            $this->props,
            $this->url,
            $this->version
        );
    }

    /**
     * Serialize to JSON for Inertia.js protocol.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'component' => $this->component,
            'props' => $this->props,
            'url' => $this->url,
            'version' => $this->version,
        ];
    }
}
