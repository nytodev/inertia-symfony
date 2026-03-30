<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class FlashTest extends FunctionalTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    // -------------------------------------------------------------------------
    // flash is always present in page object (even when empty)
    // -------------------------------------------------------------------------

    public function testFlashIsAlwaysPresentInPageObjectWhenNoFlashSetXhrRequest(): void
    {
        $this->client->request('GET', '/test', [], [], ['HTTP_X_INERTIA' => 'true']);
        self::assertResponseIsSuccessful();
        $data = $this->decodeJsonResponse();
        self::assertArrayHasKey('flash', $data);
        self::assertSame([], $data['flash']);
    }

    public function testFlashIsAlwaysPresentInPageObjectWhenNoFlashSetFirstVisit(): void
    {
        $this->client->request('GET', '/test');
        self::assertResponseIsSuccessful();
        $page = $this->extractPageObject();
        self::assertArrayHasKey('flash', $page);
        self::assertSame([], $page['flash']);
    }

    // -------------------------------------------------------------------------
    // flash data set on same request appears in page object
    // -------------------------------------------------------------------------

    public function testFlashDataAppearsInPageObjectXhrRequest(): void
    {
        $this->client->request('GET', '/test/flash-direct', [], [], ['HTTP_X_INERTIA' => 'true']);
        self::assertResponseIsSuccessful();
        $data = $this->decodeJsonResponse();
        self::assertSame(['status' => 'saved'], $data['flash']);
    }

    public function testFlashDataAppearsInPageObjectFirstVisit(): void
    {
        $this->client->request('GET', '/test/flash-direct');
        self::assertResponseIsSuccessful();
        $page = $this->extractPageObject();
        self::assertSame(['status' => 'saved'], $page['flash']);
    }

    // -------------------------------------------------------------------------
    // flash is consumed (cleared) after render
    // -------------------------------------------------------------------------

    public function testFlashIsConsumedAfterRenderSecondRequestShowsEmptyFlash(): void
    {
        // First request: flash is set and rendered
        $this->client->request('GET', '/test/flash-direct', [], [], ['HTTP_X_INERTIA' => 'true']);
        $data = $this->decodeJsonResponse();
        self::assertSame(['status' => 'saved'], $data['flash']);

        // Second request to a render-only route: flash must be gone
        $this->client->request('GET', '/test/flash-target', [], [], ['HTTP_X_INERTIA' => 'true']);
        $data = $this->decodeJsonResponse();
        self::assertSame([], $data['flash']);
    }

    // -------------------------------------------------------------------------
    // flash survives a redirect (stored in session, read on the next render)
    // -------------------------------------------------------------------------

    public function testFlashSurvivesRedirectFlashAppearsAfter303(): void
    {
        $this->client->followRedirects(false);
        // X-Inertia must be set via setServerParameter so followRedirect() inherits it.
        $this->client->setServerParameter('HTTP_X_INERTIA', 'true');

        // PUT → controller sets flash + returns 302 → listener converts to 303
        $this->client->request('PUT', '/test/flash-redirect');
        self::assertResponseStatusCodeSame(303);
        self::assertSame('/test/flash-target', $this->client->getResponse()->headers->get('Location'));

        // Follow redirect → GET /test/flash-target → flash must be in page object
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        $data = $this->decodeJsonResponse();
        self::assertSame(['status' => 'saved'], $data['flash']);
    }

    public function testFlashIsConsumedAfterRedirectRenderThirdRequestShowsEmptyFlash(): void
    {
        $this->client->followRedirects(false);
        $this->client->setServerParameter('HTTP_X_INERTIA', 'true');

        // PUT → 303 redirect
        $this->client->request('PUT', '/test/flash-redirect');
        self::assertResponseStatusCodeSame(303);

        // Follow redirect → flash present
        $this->client->followRedirect();
        $data = $this->decodeJsonResponse();
        self::assertSame(['status' => 'saved'], $data['flash']);

        // Third request → flash consumed, must be empty
        $this->client->request('GET', '/test/flash-target');
        $data = $this->decodeJsonResponse();
        self::assertSame([], $data['flash']);
    }

    // -------------------------------------------------------------------------
    // helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(): array
    {
        $decoded = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertIsArray($decoded);

        return $decoded;
    }

    /**
     * Parse the data-page JSON from a first-visit HTML response.
     *
     * @return array<string, mixed>
     */
    private function extractPageObject(): array
    {
        $content = (string) $this->client->getResponse()->getContent();
        $matched = preg_match('/data-page=\'(.+?)\'/', $content, $matches);
        self::assertSame(1, $matched, 'data-page attribute not found in HTML response');
        $page = json_decode($matches[1] ?? '', true);
        self::assertIsArray($page);

        return $page;
    }
}
