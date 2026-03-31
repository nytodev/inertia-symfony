<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Tests\Functional\app\VersionedKernel;
use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Verifies the Inertia.js v2 asset versioning protocol:
 * - Version match → 200
 * - Version mismatch on GET → 409 Conflict + X-Inertia-Location header
 * - Non-GET requests → no version check
 * - null version → no version check
 *
 * Uses VersionedKernel which injects version='v1' via a compiler pass.
 * The unversioned assertions use a separate client against the default TestKernel.
 */
final class AssetVersionTest extends FunctionalTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new VersionedKernel('v1');
    }

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        $this->client->followRedirects(false);
    }

    // -------------------------------------------------------------------------
    // Version match → 200
    // -------------------------------------------------------------------------

    public function testAssetVersionWhenVersionMatchesReturns200(): void
    {
        $this->client->request('GET', '/test', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_VERSION' => 'v1',
        ]);

        self::assertResponseIsSuccessful();
    }

    public function testAssetVersionWhenNoVersionHeaderSentReturns409(): void
    {
        // Client sends X-Inertia but omits X-Inertia-Version: server has a version
        // configured → versions differ (null vs 'v1') → must trigger 409, same as
        // the inertia-laravel reference (header default '' !== 'v1').
        $this->client->request('GET', '/test', [], [], [
            'HTTP_X_INERTIA' => 'true',
        ]);

        self::assertResponseStatusCodeSame(409);
    }

    // -------------------------------------------------------------------------
    // Version mismatch on GET → 409 Conflict
    // -------------------------------------------------------------------------

    public function testAssetVersionWhenVersionMismatchesReturns409Conflict(): void
    {
        $this->client->request('GET', '/test', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_VERSION' => 'v2',
        ]);

        self::assertResponseStatusCodeSame(409);
    }

    public function testAssetVersionOn409HasXInertiaLocationHeader(): void
    {
        $this->client->request('GET', '/test', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_VERSION' => 'v2',
        ]);

        self::assertResponseStatusCodeSame(409);
        self::assertResponseHasHeader('X-Inertia-Location');
    }

    public function testAssetVersionOn409XInertiaLocationContainsRequestUrl(): void
    {
        $this->client->request('GET', '/test', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_VERSION' => 'v2',
        ]);

        self::assertResponseStatusCodeSame(409);

        $location = $this->client->getResponse()->headers->get('X-Inertia-Location');
        self::assertNotNull($location);
        self::assertStringEndsWith('/test', $location);
    }

    public function testAssetVersionOn409WithQueryStringXInertiaLocationPreservesQuery(): void
    {
        $this->client->request('GET', '/test', ['page' => '2'], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_VERSION' => 'v2',
        ]);

        self::assertResponseStatusCodeSame(409);

        $location = $this->client->getResponse()->headers->get('X-Inertia-Location');
        self::assertNotNull($location);
        self::assertStringContainsString('page=2', $location);
    }

    // -------------------------------------------------------------------------
    // Non-GET requests → no version check (409 must NOT fire)
    // -------------------------------------------------------------------------

    public function testAssetVersionVersionCheckOnlyForGetRequestsPostIgnored(): void
    {
        $this->client->request('POST', '/test/redirect', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_VERSION' => 'v2',
        ]);

        // POST with version mismatch must not trigger 409.
        self::assertResponseStatusCodeSame(302);
    }

    // -------------------------------------------------------------------------
    // No X-Inertia header → no version check at all
    // -------------------------------------------------------------------------

    public function testAssetVersionWhenNoInertiaHeaderNoVersionCheck(): void
    {
        $this->client->request('GET', '/test', [], [], [
            'HTTP_X_INERTIA_VERSION' => 'v2',
        ]);

        // Normal HTML request, version header without X-Inertia → no 409.
        self::assertResponseIsSuccessful();
    }
}
