<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Controller;

use Nytodev\InertiaBundle\Controller\AbstractInertiaController;
use Nytodev\InertiaBundle\Response\InertiaResponse;
use Nytodev\InertiaBundle\Service\Inertia;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class AbstractInertiaControllerTest extends TestCase
{
    /** @var RequestStack&MockObject */
    private RequestStack $requestStack;
    private Inertia $inertia;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $twig = new Environment(new ArrayLoader([
            'base.html.twig' => '<body>{{ page|json_encode }}</body>',
        ]));
        $inertiaResponse = new InertiaResponse($twig, 'base.html.twig');
        $this->inertia = new Inertia(
            $this->requestStack,
            $twig,
            $inertiaResponse,
            'base.html.twig',
            null,
            false,
            '',
        );
    }

    public function testRenderInertiaRendersComponent(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/home']);
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        // Create a concrete subclass of the abstract controller for testing.
        $controller = new class($this->inertia) extends AbstractInertiaController {
            /** @param array<string, mixed> $props */
            public function callRenderInertia(string $component, array $props = []): Response
            {
                return $this->renderInertia($component, $props);
            }
        };

        $response = $controller->callRenderInertia('TestPage', ['title' => 'Hello']);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('TestPage', (string) $response->getContent());
    }

    public function testRenderInertiaWithXhrRequestReturnsJson(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/home']);
        $request->headers->set('X-Inertia', 'true');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $controller = new class($this->inertia) extends AbstractInertiaController {
            /** @param array<string, mixed> $props */
            public function callRenderInertia(string $component, array $props = []): Response
            {
                return $this->renderInertia($component, $props);
            }
        };

        $response = $controller->callRenderInertia('TestPage', ['title' => 'Hello']);

        self::assertStringContainsString('application/json', $response->headers->get('Content-Type') ?? '');
        $data = json_decode((string) $response->getContent(), true);
        self::assertIsArray($data);
        self::assertSame('TestPage', $data['component']);
    }
}
