<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Exception;

use Nytodev\InertiaBundle\Exception\ExceptionResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class ExceptionResponseTest extends TestCase
{
    private Request $request;

    protected function setUp(): void
    {
        $this->request = Request::create('/error');
    }

    public function testStatusCodeReturnsHttpStatusFromConstructor(): void
    {
        $e = new HttpException(404, 'Not Found');
        $response = new ExceptionResponse($e, $this->request, 404);

        self::assertSame(404, $response->statusCode());
    }

    public function testStatusCodeReturns500ForGenericException(): void
    {
        $e = new \RuntimeException('Boom');
        $response = new ExceptionResponse($e, $this->request, 500);

        self::assertSame(500, $response->statusCode());
    }

    public function testHasComponentReturnsFalseBeforeRender(): void
    {
        $e = new \RuntimeException();
        $response = new ExceptionResponse($e, $this->request, 500);

        self::assertFalse($response->hasComponent());
    }

    public function testHasComponentReturnsTrueAfterRender(): void
    {
        $e = new \RuntimeException();
        $response = new ExceptionResponse($e, $this->request, 500);
        $response->render('Error/Index');

        self::assertTrue($response->hasComponent());
    }

    public function testRenderIsFluent(): void
    {
        $e = new \RuntimeException();
        $response = new ExceptionResponse($e, $this->request, 500);
        $result = $response->render('Error/Index', ['status' => 500]);

        self::assertSame($response, $result);
    }

    public function testGetComponentReturnsComponentSetByRender(): void
    {
        $e = new \RuntimeException();
        $response = new ExceptionResponse($e, $this->request, 500);
        $response->render('Error/Index');

        self::assertSame('Error/Index', $response->getComponent());
    }

    public function testGetComponentThrowsWhenNoRenderCalled(): void
    {
        $e = new \RuntimeException();
        $response = new ExceptionResponse($e, $this->request, 500);

        $this->expectException(\LogicException::class);
        $response->getComponent();
    }

    public function testGetPropsReturnsEmptyArrayByDefault(): void
    {
        $e = new \RuntimeException();
        $response = new ExceptionResponse($e, $this->request, 500);
        $response->render('Error/Index');

        self::assertSame([], $response->getProps());
    }

    public function testGetPropsReturnsPropsSetByRender(): void
    {
        $e = new \RuntimeException();
        $response = new ExceptionResponse($e, $this->request, 500);
        $response->render('Error/Index', ['status' => 404, 'message' => 'Not Found']);

        self::assertSame(['status' => 404, 'message' => 'Not Found'], $response->getProps());
    }

    public function testExceptionIsPublicReadonlyProperty(): void
    {
        $e = new \RuntimeException('test error');
        $response = new ExceptionResponse($e, $this->request, 500);

        self::assertSame($e, $response->exception);
    }

    public function testRequestIsPublicReadonlyProperty(): void
    {
        $e = new \RuntimeException();
        $response = new ExceptionResponse($e, $this->request, 404);

        self::assertSame($this->request, $response->request);
    }
}
