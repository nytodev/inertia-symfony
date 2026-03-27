<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Service;

use Nytodev\InertiaBundle\Response\InertiaResponse;
use Nytodev\InertiaBundle\Service\Inertia;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class InertiaTest extends TestCase
{
    /** @var RequestStack&MockObject */
    private RequestStack $requestStack;
    private Environment $twig;
    private InertiaResponse $inertiaResponse;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->twig = new Environment(new ArrayLoader([
            'base.html.twig' => '<body>{{ page|json_encode }}</body>',
        ]));
        $this->inertiaResponse = new InertiaResponse($this->twig, 'base.html.twig');
    }

    private function makeService(?string $version = null): Inertia
    {
        return new Inertia(
            $this->requestStack,
            $this->twig,
            $this->inertiaResponse,
            'base.html.twig',
            $version,
            false,
            '',
        );
    }

    public function testRenderWithBasicComponentReturnsResponse(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/home']);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $service = $this->makeService();
        $response = $service->render('Home', ['foo' => 'bar']);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(200, $response->getStatusCode());
    }

    public function testRenderMergesSharedPropsWithComponentProps(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/home']);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $service = $this->makeService();
        $service->share('shared', 'data');
        $response = $service->render('Home', ['local' => 'value']);

        $content = (string) $response->getContent();
        self::assertStringContainsString('shared', $content);
        self::assertStringContainsString('local', $content);
    }

    public function testRenderMergesSharedOncePropsWithComponentProps(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/home']);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $service = $this->makeService();
        $service->shareOnce('once', 'flash');
        $response = $service->render('Home', ['local' => 'value']);

        $content = (string) $response->getContent();
        self::assertStringContainsString('once', $content);
        self::assertStringContainsString('local', $content);
    }

    public function testRenderWithInertiaXhrRequestReturnsJsonResponse(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/home']);
        $request->headers->set('X-Inertia', 'true');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $service = $this->makeService();
        $response = $service->render('Home', []);

        self::assertStringContainsString('application/json', $response->headers->get('Content-Type') ?? '');
        $data = json_decode((string) $response->getContent(), true);
        self::assertIsArray($data);
        self::assertSame('Home', $data['component']);
    }

    public function testShareWithKeyValueSharedPropAvailableOnNextRender(): void
    {
        $service = $this->makeService();
        $service->share('auth', ['user' => 'Tony']);

        self::assertSame(['auth' => ['user' => 'Tony']], $service->getSharedProps());
    }

    public function testShareOnceWithKeyValuePropClearedAfterFlush(): void
    {
        $service = $this->makeService();
        $service->shareOnce('flash', 'success');

        self::assertSame(['flash' => 'success'], $service->getSharedOnceProps());

        $service->flushSharedOnceProps();

        self::assertSame([], $service->getSharedOnceProps());
    }

    public function testVersionWhenConfiguredReturnsVersionString(): void
    {
        $service = $this->makeService('abc123');
        self::assertSame('abc123', $service->version());
    }

    public function testVersionWhenNotConfiguredReturnsNull(): void
    {
        $service = $this->makeService();
        self::assertNull($service->version());
    }

    public function testGetSharedPropsReturnsAllSharedProps(): void
    {
        $service = $this->makeService();
        $service->share('key1', 'value1');
        $service->share('key2', 'value2');

        self::assertSame(['key1' => 'value1', 'key2' => 'value2'], $service->getSharedProps());
    }

    public function testFlushSharedOncePropsClearsOnceProps(): void
    {
        $service = $this->makeService();
        $service->shareOnce('a', 1);
        $service->shareOnce('b', 2);

        $service->flushSharedOnceProps();

        self::assertSame([], $service->getSharedOnceProps());
    }

    public function testRenderThrowsLogicExceptionWhenNoCurrentRequest(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $service = $this->makeService();

        $this->expectException(\LogicException::class);
        $service->render('Home', []);
    }
}
