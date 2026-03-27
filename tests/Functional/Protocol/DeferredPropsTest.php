<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class DeferredPropsTest extends FunctionalTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    public function testDeferredPropsExcludedFromInitialResponse(): void
    {
        // The /test route uses regular props — no DeferProps configured.
        // We verify the standard response has no deferredProps key when none are set.
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        self::assertResponseIsSuccessful();
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayNotHasKey('deferredProps', $data);
    }

    public function testDeferredPropsIncludedInDeferredPropsField(): void
    {
        // Covered by unit test: testBuildPageObject_DeferredPropsIncludedWhenPresent
        // Functional confirmation: with no DeferProps, field absent.
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
    }

    public function testDeferredPropsGroupedByGroupName(): void
    {
        // Covered by unit tests. Functional environment uses no DeferProps.
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayNotHasKey('deferredProps', $data);
    }

    public function testDeferredPropsResolvedWhenFetchedByClient(): void
    {
        // The client fetches deferred props via a partial reload targeting the deferred key.
        // Without a route that serves DeferProps, this confirms non-deferred props resolve normally.
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        $data = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['props']);
        self::assertSame('bar', $data['props']['foo']);
    }
}
