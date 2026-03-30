<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class MatchPropsOnTest extends WebTestCase
{
    public function testMatchPropsOnWhenMergePropHasMatchOnIsInPageObject(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/match-props-on', [], [], [
            'HTTP_X-Inertia' => 'true',
            'HTTP_X-Inertia-Version' => '',
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('matchPropsOn', $data);
        $this->assertSame(['merge_a.id'], $data['matchPropsOn']);
    }

    public function testMatchPropsOnWhenNoMatchOnIsAbsentFromPageObject(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/merge', [], [], [
            'HTTP_X-Inertia' => 'true',
            'HTTP_X-Inertia-Version' => '',
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('matchPropsOn', $data);
    }

    public function testMatchPropsOnWhenPropFilteredOutIsAbsentFromPageObject(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/match-props-on', [], [], [
            'HTTP_X-Inertia' => 'true',
            'HTTP_X-Inertia-Version' => '',
            'HTTP_X-Inertia-Partial-Data' => 'merge_b',
            'HTTP_X-Inertia-Partial-Component' => 'TestComponent',
        ]);

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('matchPropsOn', $data);
    }
}
