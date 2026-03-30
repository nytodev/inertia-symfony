<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\EventListener;

use Nytodev\InertiaBundle\EventListener\InertiaListener;
use Nytodev\InertiaBundle\Response\InertiaResponse;
use Nytodev\InertiaBundle\Service\Inertia;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class InertiaListenerTest extends TestCase
{
    private Inertia $inertia;
    private InertiaListener $listener;

    protected function setUp(): void
    {
        $requestStack = $this->createMock(RequestStack::class);
        $twig = new Environment(new ArrayLoader([]));
        $inertiaResponse = new InertiaResponse($twig, 'base.html.twig');

        $this->inertia = new Inertia($requestStack, $inertiaResponse, 'server-v1');
        $this->listener = new InertiaListener($this->inertia);
    }

    private function makeKernel(): HttpKernelInterface
    {
        return $this->createMock(HttpKernelInterface::class);
    }

    public function testOnKernelRequestWithoutInertiaHeaderDoesNothing(): void
    {
        $request = Request::create('/test');
        $event = new RequestEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestWithMatchingVersionDoesNotReturn409(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Version', 'server-v1');
        $event = new RequestEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestWithMismatchedVersionReturns409(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Version', 'old-v1');
        $event = new RequestEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onKernelRequest($event);

        self::assertTrue($event->hasResponse());
        self::assertSame(409, $event->getResponse()->getStatusCode());
    }

    public function testOnKernelRequestOn409SetsXInertiaLocationHeader(): void
    {
        $request = Request::create('http://example.com/test');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Version', 'old-v1');
        $event = new RequestEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onKernelRequest($event);

        self::assertTrue($event->hasResponse());
        self::assertNotEmpty($event->getResponse()->headers->get('X-Inertia-Location'));
    }

    public function testOnKernelRequestWithVersionMismatchOnlyOnGetMethod(): void
    {
        $request = Request::create('/test', 'POST');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Version', 'old-v1');
        $event = new RequestEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST);

        $this->listener->onKernelRequest($event);

        // POST is not GET — no 409
        self::assertFalse($event->hasResponse());
    }

    public function testOnKernelRequestSubRequestDoesNothing(): void
    {
        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Version', 'old-v1');
        $event = new RequestEvent($this->makeKernel(), $request, HttpKernelInterface::SUB_REQUEST);

        $this->listener->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testOnKernelResponseAfter302OnPutConvertedTo303(): void
    {
        $request = Request::create('/test', 'PUT');
        $request->headers->set('X-Inertia', 'true');
        $response = new Response('', 302);
        $event = new ResponseEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertSame(303, $event->getResponse()->getStatusCode());
    }

    public function testOnKernelResponseAfter302OnPatchConvertedTo303(): void
    {
        $request = Request::create('/test', 'PATCH');
        $request->headers->set('X-Inertia', 'true');
        $response = new Response('', 302);
        $event = new ResponseEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertSame(303, $event->getResponse()->getStatusCode());
    }

    public function testOnKernelResponseAfter302OnDeleteConvertedTo303(): void
    {
        $request = Request::create('/test', 'DELETE');
        $request->headers->set('X-Inertia', 'true');
        $response = new Response('', 302);
        $event = new ResponseEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertSame(303, $event->getResponse()->getStatusCode());
    }

    public function testOnKernelResponseAfter302OnGetRemains302(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Inertia', 'true');
        $response = new Response('', 302);
        $event = new ResponseEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertSame(302, $event->getResponse()->getStatusCode());
    }

    public function testOnKernelResponseNonInertiaRequestDoesNotConvert(): void
    {
        $request = Request::create('/test', 'PUT');
        // No X-Inertia header
        $response = new Response('', 302);
        $event = new ResponseEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertSame(302, $event->getResponse()->getStatusCode());
    }

    public function testOnKernelResponseFlushesSharedOncePropsAfterInertiaJsonRender(): void
    {
        $this->inertia->shareOnce('flash', 'value');
        self::assertSame(['flash' => 'value'], $this->inertia->getSharedOnceProps());

        $request = Request::create('/test', 'GET');
        // Response must carry X-Inertia header (set by InertiaResponse on JSON renders).
        $response = new JsonResponse([], 200, ['X-Inertia' => 'true']);
        $event = new ResponseEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertSame([], $this->inertia->getSharedOnceProps());
    }

    public function testOnKernelResponseDoesNotFlushOncePropsWhenResponseHasNoXInertiaHeader(): void
    {
        $this->inertia->shareOnce('flash', 'value');

        $request = Request::create('/test', 'GET');
        $response = new Response('', 200);
        $event = new ResponseEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertSame(['flash' => 'value'], $this->inertia->getSharedOnceProps());
    }

    public function testOnKernelResponseDoesNotFlushOncePropsOn409(): void
    {
        $this->inertia->shareOnce('flash', 'value');

        $request = Request::create('/test', 'GET');
        $request->headers->set('X-Inertia', 'true');
        // 409 response from version mismatch — no X-Inertia response header.
        $response = new Response('', 409, ['X-Inertia-Location' => '/test']);
        $event = new ResponseEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertSame(['flash' => 'value'], $this->inertia->getSharedOnceProps());
    }

    public function testOnKernelResponseDoesNotFlushOncePropsOnRedirect(): void
    {
        $this->inertia->shareOnce('flash', 'value');

        $request = Request::create('/test', 'PUT');
        $request->headers->set('X-Inertia', 'true');
        // Redirect response — no X-Inertia response header.
        $response = new Response('', 303, ['Location' => '/other']);
        $event = new ResponseEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        self::assertSame(['flash' => 'value'], $this->inertia->getSharedOnceProps());
    }

    public function testOnKernelResponseSubRequestDoesNothing(): void
    {
        $this->inertia->shareOnce('flash', 'value');

        $request = Request::create('/test', 'GET');
        $response = new JsonResponse([], 200, ['X-Inertia' => 'true']);
        $event = new ResponseEvent($this->makeKernel(), $request, HttpKernelInterface::SUB_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        // Sub-request: no flush, no status change.
        self::assertSame(['flash' => 'value'], $this->inertia->getSharedOnceProps());
    }

    public function testOnKernelRequestOn409WithFlashBagAwareSessionReflashesFlashData(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('notice', 'Keep me');
        $session->getFlashBag()->add('error', 'Keep me too');

        $request = Request::create('http://example.com/page');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Version', 'old-version');
        $request->setSession($session);

        $event = new RequestEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST);
        $this->listener->onKernelRequest($event);

        self::assertNotNull($event->getResponse());
        self::assertSame(409, $event->getResponse()->getStatusCode());
        $remaining = $session->getFlashBag()->peekAll();
        self::assertArrayHasKey('notice', $remaining);
        self::assertArrayHasKey('error', $remaining);
        self::assertContains('Keep me', $remaining['notice']);
        self::assertContains('Keep me too', $remaining['error']);
    }

    public function testOnKernelRequestOn409WithNonFlashBagAwareSessionReturns409WithoutError(): void
    {
        $session = $this->createMock(SessionInterface::class);

        $request = Request::create('http://example.com/page');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Version', 'old-version');
        $request->setSession($session);

        $event = new RequestEvent($this->makeKernel(), $request, HttpKernelInterface::MAIN_REQUEST);
        $this->listener->onKernelRequest($event);

        self::assertNotNull($event->getResponse());
        self::assertSame(409, $event->getResponse()->getStatusCode());
    }
}
