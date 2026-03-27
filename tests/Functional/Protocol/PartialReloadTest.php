<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class PartialReloadTest extends FunctionalTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    public function testPartialReloadWithPartialDataHeaderReturnsOnlyRequestedProps(): void
    {
        $this->client->request('GET', '/test/partial', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_PARTIAL_DATA' => 'foo',
            'HTTP_X_INERTIA_PARTIAL_COMPONENT' => 'TestComponent',
        ]);
        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['props']);
        self::assertArrayHasKey('foo', $data['props']);
        self::assertArrayNotHasKey('baz', $data['props']);
    }

    public function testPartialReloadWithPartialExceptHeaderExcludesListedProps(): void
    {
        $this->client->request('GET', '/test/partial', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_PARTIAL_EXCEPT' => 'baz',
            'HTTP_X_INERTIA_PARTIAL_COMPONENT' => 'TestComponent',
        ]);
        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['props']);
        self::assertArrayHasKey('foo', $data['props']);
        self::assertArrayNotHasKey('baz', $data['props']);
    }

    public function testPartialReloadErrorsPropAlwaysIncluded(): void
    {
        $this->client->request('GET', '/test/partial', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_PARTIAL_DATA' => 'foo',
            'HTTP_X_INERTIA_PARTIAL_COMPONENT' => 'TestComponent',
        ]);
        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['props']);
        self::assertArrayHasKey('errors', $data['props']);
    }

    public function testPartialReloadLazyPropResolvedWhenRequested(): void
    {
        $this->client->request('GET', '/test/lazy', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_PARTIAL_DATA' => 'lazy',
            'HTTP_X_INERTIA_PARTIAL_COMPONENT' => 'TestComponent',
        ]);
        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['props']);
        self::assertArrayHasKey('lazy', $data['props']);
        self::assertSame('lazy-value', $data['props']['lazy']);
    }

    public function testPartialReloadLazyPropSkippedWhenNotRequested(): void
    {
        $this->client->request('GET', '/test/lazy', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_PARTIAL_COMPONENT' => 'TestComponent',
        ]);
        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['props']);
        self::assertArrayNotHasKey('lazy', $data['props']);
    }

    public function testPartialReloadWithMismatchedComponentReturnsFullResponse(): void
    {
        $this->client->request('GET', '/test/partial', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_PARTIAL_DATA' => 'foo',
            'HTTP_X_INERTIA_PARTIAL_COMPONENT' => 'WrongComponent',
        ]);
        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['props']);
        // Partial filtering must NOT be applied — both props must be present
        self::assertArrayHasKey('foo', $data['props']);
        self::assertArrayHasKey('baz', $data['props']);
    }

    public function testPartialReloadExceptWinsOverOnlyWhenBothPresent(): void
    {
        $this->client->request('GET', '/test/partial', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_PARTIAL_DATA' => 'foo,baz',
            'HTTP_X_INERTIA_PARTIAL_EXCEPT' => 'baz',
            'HTTP_X_INERTIA_PARTIAL_COMPONENT' => 'TestComponent',
        ]);
        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['props']);
        self::assertArrayHasKey('foo', $data['props']);
        self::assertArrayNotHasKey('baz', $data['props']);
    }
}
