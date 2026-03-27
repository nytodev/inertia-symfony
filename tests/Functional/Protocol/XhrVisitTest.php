<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class XhrVisitTest extends FunctionalTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    public function testXhrVisitWithXInertiaHeaderReturnsJsonResponse(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('component', $data);
    }

    public function testXhrVisitResponseHasXInertiaHeader(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        self::assertResponseHasHeader('X-Inertia');
    }

    public function testXhrVisitResponseHasVaryXInertiaHeader(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        self::assertResponseHasHeader('Vary');
        self::assertStringContainsString('X-Inertia', (string) $this->client->getResponse()->headers->get('Vary'));
    }

    public function testXhrVisitContentTypeIsApplicationJson(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        self::assertStringContainsString('application/json', (string) $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testXhrVisitPageObjectContainsAllRequiredV2Fields(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('component', $data);
        self::assertArrayHasKey('props', $data);
        self::assertArrayHasKey('url', $data);
        self::assertArrayHasKey('version', $data);
        self::assertArrayHasKey('clearHistory', $data);
        self::assertArrayHasKey('encryptHistory', $data);
    }

    public function testXhrVisitComponentMatchesRenderedComponent(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertSame('TestComponent', $data['component']);
    }

    public function testXhrVisitPropsContainRenderedData(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['props']);
        self::assertSame('bar', $data['props']['foo']);
    }
}
