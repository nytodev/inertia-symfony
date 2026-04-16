<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Exception\ExceptionResponse;
use Nytodev\InertiaBundle\Service\Inertia;
use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class ExceptionHandlingTest extends FunctionalTestCase
{
    private KernelBrowser $client;
    private Inertia $inertia;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        /** @var Inertia $inertia */
        $inertia = self::getContainer()->get(Inertia::class);
        $this->inertia = $inertia;
    }

    public function testNoCallbackExceptionNotInterceptedDefaultErrorResponse(): void
    {
        // No handleExceptionsUsing() registered — listener is a no-op.
        $this->client->catchExceptions(true);
        $this->client->request('GET', '/test/throw-not-found', [], [], ['HTTP_X_INERTIA' => 'true']);

        // Response must NOT have X-Inertia header (not an Inertia response)
        self::assertFalse($this->client->getResponse()->headers->has('X-Inertia'));
    }

    public function testCallbackHttpException404RendersInertiaComponentWithStatus404(): void
    {
        $this->inertia->handleExceptionsUsing(static function (ExceptionResponse $e): void {
            $e->render('Error/Index', ['status' => $e->statusCode()]);
        });

        $this->client->catchExceptions(true);
        $this->client->request('GET', '/test/throw-not-found', [], [], ['HTTP_X_INERTIA' => 'true']);

        $response = $this->client->getResponse();
        self::assertSame(404, $response->getStatusCode());
        self::assertTrue($response->headers->has('X-Inertia'));
        self::assertSame('true', $response->headers->get('X-Inertia'));

        $data = json_decode((string) $response->getContent(), true);
        self::assertIsArray($data);
        self::assertSame('Error/Index', $data['component']);
        self::assertSame(404, $data['props']['status']);
    }

    public function testCallbackGenericExceptionRendersInertiaComponentWithStatus500(): void
    {
        $this->inertia->handleExceptionsUsing(static function (ExceptionResponse $e): void {
            $e->render('Error/Index', ['status' => $e->statusCode()]);
        });

        $this->client->catchExceptions(true);
        $this->client->request('GET', '/test/throw-server-error', [], [], ['HTTP_X_INERTIA' => 'true']);

        $response = $this->client->getResponse();
        self::assertSame(500, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);
        self::assertIsArray($data);
        self::assertSame('Error/Index', $data['component']);
        self::assertSame(500, $data['props']['status']);
    }

    public function testCallbackNonInertiaRequestNotIntercepted(): void
    {
        // Listener must only fire on X-Inertia requests.
        // A plain HTML request must receive Symfony's default error response,
        // even when handleExceptionsUsing() is registered.
        $this->inertia->handleExceptionsUsing(static function (ExceptionResponse $e): void {
            $e->render('Error/Index', ['status' => $e->statusCode()]);
        });

        $this->client->catchExceptions(true);
        $this->client->request('GET', '/test/throw-not-found'); // no X-Inertia header

        $response = $this->client->getResponse();
        self::assertSame(404, $response->getStatusCode());
        // Not an Inertia response — no X-Inertia header, no page object script tag.
        self::assertFalse($response->headers->has('X-Inertia'));
        self::assertStringNotContainsString('<script data-page="app" type="application/json">', (string) $response->getContent());
    }

    public function testCallbackFluentReturnStyleSetsResponse(): void
    {
        $this->inertia->handleExceptionsUsing(
            static fn (ExceptionResponse $e): ExceptionResponse => $e->render('Error/Index', ['status' => $e->statusCode()])
        );

        $this->client->catchExceptions(true);
        $this->client->request('GET', '/test/throw-not-found', [], [], ['HTTP_X_INERTIA' => 'true']);

        $response = $this->client->getResponse();
        self::assertSame(404, $response->getStatusCode());
        self::assertTrue($response->headers->has('X-Inertia'));
    }

    public function testCallbackExceptionObjectAccessibleViaExceptionProperty(): void
    {
        $captured = null;
        $this->inertia->handleExceptionsUsing(static function (ExceptionResponse $e) use (&$captured): void {
            $captured = $e->exception;
            $e->render('Error/Index');
        });

        $this->client->catchExceptions(true);
        $this->client->request('GET', '/test/throw-not-found', [], [], ['HTTP_X_INERTIA' => 'true']);

        self::assertInstanceOf(\Throwable::class, $captured);
    }
}
