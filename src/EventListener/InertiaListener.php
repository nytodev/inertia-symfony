<?php

declare(strict_types=1);

namespace Nytodev\InertiaSymfony\EventListener;

use Nytodev\InertiaSymfony\Service\VersionStrategyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event listener that handles Inertia.js protocol requirements.
 *
 * This listener intercepts HTTP responses and:
 * - Adds required Inertia headers to JSON responses
 * - Handles redirect status codes (303 vs 409)
 * - Checks for asset version mismatches
 *
 * @see https://inertiajs.com/the-protocol
 */
final class InertiaListener implements EventSubscriberInterface
{
    /**
     * @param VersionStrategyInterface $versionStrategy Asset versioning strategy
     */
    public function __construct(
        private readonly VersionStrategyInterface $versionStrategy
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    /**
     * Handle the kernel response event.
     *
     * @param ResponseEvent $event The response event
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        // Only process Inertia requests
        if (!$this->isInertiaRequest($request->headers->get('X-Inertia'))) {
            return;
        }

        // Handle redirects
        if ($response instanceof RedirectResponse) {
            $this->handleRedirect($response);
            return;
        }

        // Handle version mismatch
        $clientVersion = $request->headers->get('X-Inertia-Version');
        if ($this->hasVersionMismatch($clientVersion)) {
            $this->forceReload($event);
            return;
        }

        // Add Inertia headers to JSON responses
        if ($response instanceof JsonResponse) {
            $this->addInertiaHeaders($response);
        }
    }

    /**
     * Check if the request is an Inertia request.
     *
     * @param string|null $headerValue The X-Inertia header value
     * @return bool True if Inertia request, false otherwise
     */
    private function isInertiaRequest(?string $headerValue): bool
    {
        return $headerValue === 'true';
    }

    /**
     * Handle redirect responses for Inertia requests.
     *
     * Inertia requires specific status codes for redirects:
     * - 303 See Other: For internal navigation (PUT/PATCH/DELETE -> GET)
     * - 409 Conflict: For external redirects (force full page reload)
     *
     * @param RedirectResponse $response The redirect response
     */
    private function handleRedirect(RedirectResponse $response): void
    {
        // For Inertia requests, always use 303 for redirects
        // This ensures PUT/PATCH/DELETE requests redirect to GET
        if ($response->getStatusCode() === 302) {
            $response->setStatusCode(303);
        }
    }

    /**
     * Check if the client asset version differs from the server version.
     *
     * @param string|null $clientVersion The client's asset version
     * @return bool True if versions mismatch, false otherwise
     */
    private function hasVersionMismatch(?string $clientVersion): bool
    {
        if ($clientVersion === null) {
            return false;
        }

        return $clientVersion !== $this->versionStrategy->getVersion();
    }

    /**
     * Force a full page reload due to asset version mismatch.
     *
     * When the client's asset version is stale, send a 409 Conflict response
     * to trigger a full page reload and fetch new assets.
     *
     * @param ResponseEvent $event The response event
     */
    private function forceReload(ResponseEvent $event): void
    {
        $response = new JsonResponse(['message' => 'Asset version mismatch'], 409);
        $response->headers->set('X-Inertia-Location', $event->getRequest()->getRequestUri());

        $event->setResponse($response);
    }

    /**
     * Add required Inertia headers to JSON responses.
     *
     * @param JsonResponse $response The JSON response
     */
    private function addInertiaHeaders(JsonResponse $response): void
    {
        $response->headers->set('X-Inertia', 'true');
        $response->headers->set('X-Inertia-Version', $this->versionStrategy->getVersion());
        $response->headers->set('Vary', 'X-Inertia');
    }
}
