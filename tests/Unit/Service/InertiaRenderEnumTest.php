<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Service;

use Nytodev\InertiaBundle\Response\InertiaResponse;
use Nytodev\InertiaBundle\Service\Inertia;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

enum TestStringBackedEnum: string
{
    case UsersIndex = 'UsersPage/Index';
}

enum TestIntBackedEnum: int
{
    case Zero = 0;
}

enum TestUnitEnum
{
    case Dashboard;
}

final class InertiaRenderEnumTest extends TestCase
{
    /** @var RequestStack&MockObject */
    private RequestStack $requestStack;
    private InertiaResponse $inertiaResponse;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $twig = new Environment(new ArrayLoader([
            'base.html.twig' => '<body>{{ page|json_encode }}</body>',
        ]));
        $this->inertiaResponse = new InertiaResponse($twig, 'base.html.twig');
    }

    private function makeService(): Inertia
    {
        return new Inertia($this->requestStack, $this->inertiaResponse, null);
    }

    private function makeRequest(): Request
    {
        return Request::create('/');
    }

    public function testRenderStringBackedEnumResolvesToEnumValue(): void
    {
        $request = $this->makeRequest();
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $response = $this->makeService()->render(TestStringBackedEnum::UsersIndex);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('UsersPage\/Index', (string) $response->getContent());
    }

    public function testRenderUnitEnumResolvesToEnumName(): void
    {
        $request = $this->makeRequest();
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $response = $this->makeService()->render(TestUnitEnum::Dashboard);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Dashboard', (string) $response->getContent());
    }

    public function testRenderIntBackedEnumThrowsInvalidArgumentException(): void
    {
        $request = $this->makeRequest();
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Component name must resolve to a string (int-backed enums are not supported).');

        $this->makeService()->render(TestIntBackedEnum::Zero);
    }

    public function testRenderStringPassedThrough(): void
    {
        $request = $this->makeRequest();
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $response = $this->makeService()->render('Users/Index');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Users\/Index', (string) $response->getContent());
    }
}
