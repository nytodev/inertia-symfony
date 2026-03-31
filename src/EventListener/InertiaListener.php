<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\EventListener;

use Nytodev\InertiaBundle\Service\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Handles two Inertia protocol concerns at the kernel level:
 *
 * kernel.request (priority 20):
 *   - FlashBag 'errors' → auto-injected as Inertia validation errors (all methods)
 *   - Asset version mismatch on GET → 409 Conflict + X-Inertia-Location header
 *   - Reflash session flash data before returning 409
 *
 * kernel.response (priority 0):
 *   - 302 after PUT/PATCH/DELETE → 303 See Other
 */
final class InertiaListener
{
    public function __construct(
        private readonly Inertia $inertia,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if (!$request->headers->has('X-Inertia')) {
            return;
        }

        // Auto-inject FlashBag 'errors' as Inertia validation errors.
        // Runs on all HTTP methods so a non-redirecting POST/PUT can also surface errors.
        // Done before the version check so errors survive a 409 + hard reload.
        if ($request->hasSession()) {
            $session = $request->getSession();
            if ($session instanceof FlashBagAwareSessionInterface) {
                foreach ($session->getFlashBag()->get('errors') as $errors) {
                    if (\is_array($errors)) {
                        $this->inertia->errors($errors);
                    }
                }
            }
        }

        if (!$request->isMethod('GET')) {
            return;
        }

        $serverVersion = $this->inertia->version();

        if (null === $serverVersion) {
            return;
        }

        $clientVersion = $request->headers->get('X-Inertia-Version');

        if ($clientVersion === $serverVersion) {
            return;
        }

        // Reflash session flash data so it is not lost across the 409 redirect.
        if ($request->hasSession()) {
            $session = $request->getSession();
            if ($session instanceof FlashBagAwareSessionInterface) {
                $flashes = $session->getFlashBag()->peekAll();
                foreach ($flashes as $type => $messages) {
                    foreach ($messages as $message) {
                        $session->getFlashBag()->add($type, $message);
                    }
                }
            }
        }

        $event->setResponse(new Response('', 409, [
            'X-Inertia-Location' => $request->getUri(),
        ]));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if (
            $request->headers->has('X-Inertia')
            && 302 === $response->getStatusCode()
            && \in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'], true)
        ) {
            $response->setStatusCode(303);
        }
    }
}
