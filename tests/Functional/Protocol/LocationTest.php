<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class LocationTest extends FunctionalTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        $this->client->followRedirects(false);
    }

    public function testLocationXhrRequestReturns409(): void
    {
        $this->client->request('GET', '/test/location', [], [], [
            'HTTP_X_INERTIA' => 'true',
        ]);

        self::assertResponseStatusCodeSame(409);
    }

    public function testLocationXhrRequestHasXInertiaLocationHeader(): void
    {
        $this->client->request('GET', '/test/location', [], [], [
            'HTTP_X_INERTIA' => 'true',
        ]);

        self::assertResponseHasHeader('X-Inertia-Location');
        self::assertSame(
            'https://example.com/payment',
            $this->client->getResponse()->headers->get('X-Inertia-Location'),
        );
    }

    public function testLocationXhrRequestNoXInertiaResponseHeader(): void
    {
        $this->client->request('GET', '/test/location', [], [], [
            'HTTP_X_INERTIA' => 'true',
        ]);

        self::assertResponseNotHasHeader('X-Inertia');
    }

    public function testLocationNonXhrRequestReturns302(): void
    {
        $this->client->request('GET', '/test/location');

        self::assertResponseStatusCodeSame(302);
    }

    public function testLocationNonXhrRequestRedirectsToCorrectUrl(): void
    {
        $this->client->request('GET', '/test/location');

        self::assertResponseRedirects('https://example.com/payment');
    }

    public function testLocationWithInternalUrlXhrReturns409WithCorrectHeader(): void
    {
        $this->client->request('GET', '/test/location-internal', [], [], [
            'HTTP_X_INERTIA' => 'true',
        ]);

        self::assertResponseStatusCodeSame(409);
        self::assertSame(
            '/other-page',
            $this->client->getResponse()->headers->get('X-Inertia-Location'),
        );
    }

    public function testLocationWithInternalUrlNonXhrReturns302(): void
    {
        $this->client->request('GET', '/test/location-internal');

        self::assertResponseStatusCodeSame(302);
        self::assertResponseRedirects('/other-page');
    }
}
