<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Ssr;

/**
 * Holds the rendered HTML returned by the Node.js SSR server.
 * head: joined <head> tags (title, meta, style, …)
 * body: the pre-rendered <div id="app">…</div> HTML.
 */
final class SsrResponse
{
    public function __construct(
        public readonly string $head,
        public readonly string $body,
    ) {
    }
}
