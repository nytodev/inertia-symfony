<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\EventListener;

use Nytodev\InertiaBundle\Exception\ExceptionResponse;
use Nytodev\InertiaBundle\Service\Inertia;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Intercepts kernel.exception events and delegates to the user-registered callback.
 * If the callback calls render() on the ExceptionResponse, an Inertia response is
 * returned with the original HTTP status code preserved.
 */
final class InertiaExceptionListener
{
    public function __construct(
        private readonly Inertia $inertia,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$event->getRequest()->headers->has('X-Inertia')) {
            return;
        }

        $callback = $this->inertia->getExceptionCallback();

        if (null === $callback) {
            return;
        }

        $throwable = $event->getThrowable();
        $status = $throwable instanceof HttpExceptionInterface
            ? $throwable->getStatusCode()
            : 500;

        $exceptionResponse = new ExceptionResponse($throwable, $event->getRequest(), $status);
        $callback($exceptionResponse);

        if (!$exceptionResponse->hasComponent()) {
            return;
        }

        $response = $this->inertia->render($exceptionResponse->getComponent(), $exceptionResponse->getProps());
        $response->setStatusCode($exceptionResponse->statusCode());
        $event->setResponse($response);
        $event->allowCustomResponseCode();
    }
}
