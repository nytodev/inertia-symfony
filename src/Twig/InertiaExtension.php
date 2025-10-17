<?php

declare(strict_types=1);

namespace Nytodev\InertiaSymfony\Twig;

use JsonException;
use Nytodev\InertiaSymfony\Response\InertiaResponse;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * Twig extension providing Inertia.js template functions.
 *
 * Provides two main functions:
 * - inertia(): Renders the main app container with embedded page data
 * - inertia_head(): Renders head elements (title, meta tags)
 */
final class InertiaExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     *
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('inertia', [$this, 'renderInertia'], ['is_safe' => ['html']]),
            new TwigFunction('inertia_head', [$this, 'renderInertiaHead'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Render the main Inertia app container with embedded page data.
     *
     * Generates a <div id="app"> element with the page data encoded in a data-page attribute.
     * This is picked up by the Inertia.js client-side library to mount the Vue/React/Svelte app.
     *
     * @param InertiaResponse|array<string, mixed> $page The Inertia page data
     * @return Markup Safe HTML markup
     * @throws JsonException If JSON encoding fails
     */
    public function renderInertia(InertiaResponse|array $page): Markup
    {
        // Convert InertiaResponse to array if needed
        if ($page instanceof InertiaResponse) {
            $pageArray = $page->jsonSerialize();
        } else {
            // Ensure required page structure for raw arrays
            $pageArray = array_merge([
                'component' => '',
                'props' => [],
                'url' => '',
                'version' => null,
            ], $page);
        }

        $jsonPage = json_encode(
            $pageArray,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        return new Markup(
            sprintf(
                '<div id="app" data-page="%s"></div>',
                htmlspecialchars($jsonPage, ENT_QUOTES, 'UTF-8')
            ),
            'UTF-8'
        );
    }

    /**
     * Render head elements (title, meta tags) from page props.
     *
     * Extracts and renders:
     * - Title from $page['title'] or $page['props']['title']
     * - Meta tags from $page['props']['meta']
     * - Additional head elements from $page['props']['head']
     *
     * @param InertiaResponse|array<string, mixed> $page The Inertia page data
     * @return Markup Safe HTML markup
     */
    public function renderInertiaHead(InertiaResponse|array $page): Markup
    {
        // Convert InertiaResponse to array if needed
        if ($page instanceof InertiaResponse) {
            $pageArray = $page->jsonSerialize();
        } else {
            $pageArray = $page;
        }

        $html = '';

        // Handle title - check multiple locations
        $title = $pageArray['title'] ?? $pageArray['props']['title'] ?? null;
        if ($title !== null) {
            $html .= sprintf(
                '<title>%s</title>',
                htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8')
            );
        }

        // Handle meta tags from props.meta
        $metaTags = $pageArray['props']['meta'] ?? [];
        if (is_array($metaTags)) {
            foreach ($metaTags as $name => $content) {
                if (is_scalar($content)) {
                    $html .= sprintf(
                        '<meta name="%s" content="%s">',
                        htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string) $content, ENT_QUOTES, 'UTF-8')
                    );
                }
            }
        }

        // Handle additional head elements from props.head
        $headElements = $pageArray['props']['head'] ?? [];
        if (is_array($headElements)) {
            foreach ($headElements as $element) {
                if (is_string($element)) {
                    $html .= $element;
                }
            }
        }

        return new Markup($html, 'UTF-8');
    }
}
