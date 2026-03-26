<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\EventListener;

use Nytodev\InertiaBundle\Service\Inertia;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles two Inertia protocol concerns at the kernel level:
 *
 * kernel.request (priority 20):
 *   - Asset version mismatch on GET → 409 Conflict + X-Inertia-Location header
 *   - Reflash session flash data before returning 409
 *
 * kernel.response (priority 0):
 *   - 302 after PUT/PATCH/DELETE → 303 See Other
 *   - Flush shared once-props after response is built
 */
final class InertiaListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly Inertia $inertia,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST  => ['onKernelRequest', 20],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // TODO: implement
        // - Return early if not main request
        // - Return early if not Inertia XHR (no X-Inertia header)
        // - Return early if not GET
        // - Compare X-Inertia-Version with $this->inertia->version()
        // - If mismatch: reflash session flash, return 409 with X-Inertia-Location
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // TODO: implement
        // - Return early if not main request
        // - Convert 302 → 303 for non-GET/HEAD Inertia requests
        // - Flush once-props after response is finalized
    }
}
