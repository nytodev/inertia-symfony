<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HistoryFlagsTest extends WebTestCase
{
    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }

    public function testClearHistoryXhrRequestPageObjectHasClearHistoryTrue(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/clear-history', [], [], [
            'HTTP_X-Inertia' => 'true',
            'HTTP_X-Inertia-Version' => 'abc123',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertTrue($data['clearHistory']);
        $this->assertFalse($data['encryptHistory']);
    }

    public function testEncryptHistoryXhrRequestPageObjectHasEncryptHistoryTrue(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/encrypt-history', [], [], [
            'HTTP_X-Inertia' => 'true',
            'HTTP_X-Inertia-Version' => 'abc123',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertFalse($data['clearHistory']);
        $this->assertTrue($data['encryptHistory']);
    }

    public function testBothFlagsDefaultFalseOnNormalRenderAlwaysPresentInPageObject(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test', [], [], [
            'HTTP_X-Inertia' => 'true',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('clearHistory', $data);
        $this->assertArrayHasKey('encryptHistory', $data);
        $this->assertFalse($data['clearHistory']);
        $this->assertFalse($data['encryptHistory']);
    }

    public function testClearHistoryHtmlRequestDataPageContainsClearHistoryTrue(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/clear-history');

        $this->assertResponseIsSuccessful();
        $content = (string) $client->getResponse()->getContent();
        $this->assertStringContainsString('<script data-page="app" type="application/json">', $content);

        // Extract page object JSON from the script tag
        $matched = preg_match('/<script[^>]+type="application\/json"[^>]*>([^<]+)<\/script>/s', $content, $matches);
        $this->assertSame(1, $matched, '<script type="application/json"> tag must be present');

        $data = json_decode($matches[1] ?? '', true);
        $this->assertIsArray($data);
        $this->assertTrue($data['clearHistory']);
    }
}
