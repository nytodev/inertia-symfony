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
 *   - Asset version mismatch on GET → 409 Conflict + X-Inertia-Location header
 *   - Reflash session flash data before returning 409
 *
 * kernel.response (priority 0):
 *   - 302 after PUT/PATCH/DELETE → 303 See Other
 *   - Flush shared once-props after a successful Inertia render
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

        if (!$request->isMethod('GET')) {
            return;
        }

        $serverVersion = $this->inertia->version();

        if (null === $serverVersion) {
            return;
        }

        $clientVersion = $request->headers->get('X-Inertia-Version');

        if (null === $clientVersion || $clientVersion === $serverVersion) {
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

        // Flush once-props only after an actual Inertia JSON render (the response carries
        // the X-Inertia header). This avoids flushing on 409 version conflicts or redirects
        // where no render occurred and the once-props were never consumed.
        if ($response->headers->has('X-Inertia')) {
            $this->inertia->flushSharedOnceProps();
        }
    }
}
