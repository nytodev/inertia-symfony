<?php

declare(strict_types=1);

namespace Nytodev\InertiaSymfony\Response;

use Nytodev\InertiaSymfony\Service\PartialPropsResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Factory for creating HTTP responses from InertiaResponse DTOs.
 *
 * Handles both JSON responses (for Inertia XHR requests) and
 * HTML responses (for initial page loads).
 *
 * Supports partial reloads for optimized data fetching.
 */
final class InertiaResponseFactory
{
    /**
     * @param Environment $twig Twig environment for rendering HTML
     * @param string $rootTemplate Path to the root Inertia template
     * @param PartialPropsResolver $partialPropsResolver Service for filtering props during partial reloads
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly string $rootTemplate = 'inertia.html.twig',
        private readonly PartialPropsResolver $partialPropsResolver = new PartialPropsResolver()
    ) {
    }

    /**
     * Create an HTTP response from an InertiaResponse DTO.
     *
     * Returns JSON for Inertia XHR requests, HTML for regular requests.
     * Applies partial reload filtering when applicable.
     *
     * @param InertiaResponse $inertiaResponse The Inertia page data
     * @param Request $request The current HTTP request
     * @return Response The HTTP response
     */
    public function create(InertiaResponse $inertiaResponse, Request $request): Response
    {
        // Apply partial reload filtering
        $filteredProps = $this->partialPropsResolver->resolveProps(
            $inertiaResponse->getProps(),
            $request,
            $inertiaResponse->getComponent()
        );

        // Create new InertiaResponse with filtered props (replace, not merge)
        $filteredInertiaResponse = $inertiaResponse->withProps($filteredProps, replace: true);

        // Check if this is an Inertia XHR request
        if ($this->isInertiaRequest($request)) {
            return $this->createJsonResponse($filteredInertiaResponse);
        }

        // Regular request: return HTML
        return $this->createHtmlResponse($filteredInertiaResponse);
    }

    /**
     * Create a JSON response for Inertia XHR requests.
     *
     * @param InertiaResponse $inertiaResponse The Inertia page data
     * @return JsonResponse JSON response with Inertia headers
     */
    private function createJsonResponse(InertiaResponse $inertiaResponse): JsonResponse
    {
        $response = new JsonResponse($inertiaResponse);

        // Set required Inertia headers
        $response->headers->set('X-Inertia', 'true');
        $response->headers->set('Vary', 'X-Inertia');

        return $response;
    }

    /**
     * Create an HTML response for initial page loads.
     *
     * @param InertiaResponse $inertiaResponse The Inertia page data
     * @return Response HTML response with embedded JSON
     */
    private function createHtmlResponse(InertiaResponse $inertiaResponse): Response
    {
        $html = $this->twig->render($this->rootTemplate, [
            'page' => $inertiaResponse,
        ]);

        return new Response($html);
    }

    /**
     * Check if the request is an Inertia XHR request.
     *
     * @param Request $request The HTTP request
     * @return bool True if Inertia request, false otherwise
     */
    private function isInertiaRequest(Request $request): bool
    {
        return $request->headers->get('X-Inertia') === 'true';
    }
}
