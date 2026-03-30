<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Tests\Functional\app\Ssr\StubSsrGateway;
use Nytodev\InertiaBundle\Tests\Functional\app\SsrKernel;
use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Verifies SSR rendering: with SSR enabled, the body and head from the SSR server
 * are injected into the HTML response instead of the classic data-page div.
 *
 * Uses SsrKernel which swaps the gateway with StubSsrGateway via a compiler pass.
 */
final class SsrTest extends FunctionalTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new SsrKernel();
    }

    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    // -------------------------------------------------------------------------
    // First visit (HTML rendering)
    // -------------------------------------------------------------------------

    public function testFirstVisitSsrEnabledReturnsSsrBody(): void
    {
        $this->client->request('GET', '/test');

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString(StubSsrGateway::BODY, $content);
    }

    public function testFirstVisitSsrEnabledDoesNotContainDataPageDiv(): void
    {
        $this->client->request('GET', '/test');

        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString('data-page=', $content);
    }

    public function testFirstVisitSsrEnabledInjectsHeadContent(): void
    {
        $this->client->request('GET', '/test');

        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString(StubSsrGateway::HEAD, $content);
    }

    public function testFirstVisitSsrEnabledHeadAppearsInsideHeadTag(): void
    {
        $this->client->request('GET', '/test');

        $content = (string) $this->client->getResponse()->getContent();
        $headPos = strpos($content, StubSsrGateway::HEAD);
        $headClosePos = strpos($content, '</head>');
        self::assertNotFalse($headPos);
        self::assertNotFalse($headClosePos);
        self::assertLessThan($headClosePos, $headPos);
    }

    // -------------------------------------------------------------------------
    // XHR visit — SSR must NOT interfere (JSON response, Twig never rendered)
    // -------------------------------------------------------------------------

    public function testXhrVisitSsrEnabledReturnsJsonNotSsrBody(): void
    {
        $this->client->request('GET', '/test', [], [], [
            'HTTP_X_INERTIA' => 'true',
            'HTTP_X_INERTIA_VERSION' => '',
        ]);

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringNotContainsString(StubSsrGateway::BODY, $content);
        $data = json_decode($content, true);
        self::assertIsArray($data);
        self::assertArrayHasKey('component', $data);
    }
}
