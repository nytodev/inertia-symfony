<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class FirstVisitTest extends FunctionalTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
    }

    public function testFirstVisitWithoutXInertiaHeaderReturnsFullHtml(): void
    {
        $this->client->request('GET', '/test');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('<div id="app"', (string) $this->client->getResponse()->getContent());
    }

    public function testFirstVisitHtmlContainsDataPageAttribute(): void
    {
        $this->client->request('GET', '/test');
        self::assertStringContainsString('data-page=', (string) $this->client->getResponse()->getContent());
    }

    public function testFirstVisitDataPageJsonIsProperlyEscaped(): void
    {
        $this->client->request('GET', '/test');
        $content = (string) $this->client->getResponse()->getContent();
        // JSON_HEX_TAG means < and > are escaped as \u003C and \u003E
        self::assertStringNotContainsString('<script', $content);
    }

    public function testFirstVisitPageObjectContainsAllRequiredV2Fields(): void
    {
        $this->client->request('GET', '/test');
        $page = $this->extractPageObject();
        self::assertArrayHasKey('component', $page);
        self::assertArrayHasKey('props', $page);
        self::assertArrayHasKey('url', $page);
        self::assertArrayHasKey('version', $page);
        self::assertArrayHasKey('clearHistory', $page);
        self::assertArrayHasKey('encryptHistory', $page);
    }

    public function testFirstVisitClearHistoryAlwaysPresentInV2(): void
    {
        $this->client->request('GET', '/test');
        $page = $this->extractPageObject();
        self::assertFalse($page['clearHistory']);
    }

    public function testFirstVisitEncryptHistoryAlwaysPresentInV2(): void
    {
        $this->client->request('GET', '/test');
        $page = $this->extractPageObject();
        self::assertFalse($page['encryptHistory']);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractPageObject(): array
    {
        $content = (string) $this->client->getResponse()->getContent();
        $matched = preg_match('/data-page=\'(.+?)\'/', $content, $matches);
        self::assertSame(1, $matched, 'data-page attribute not found in response');
        $json = $matches[1] ?? null;
        self::assertNotNull($json, 'data-page capture group is empty');
        $page = json_decode($json, true);
        self::assertIsArray($page);

        return $page;
    }
}
