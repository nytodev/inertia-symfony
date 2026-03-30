<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Props;

use Nytodev\InertiaBundle\Props\OnceProp;
use PHPUnit\Framework\TestCase;

final class OncePropTest extends TestCase
{
    public function testResolveInvokesCallback(): void
    {
        $invoked = false;
        $prop = new OnceProp(static function () use (&$invoked) {
            $invoked = true;

            return 'value';
        });
        $prop->resolve();
        self::assertTrue($invoked);
    }

    public function testResolveReturnsCallbackReturnValue(): void
    {
        $prop = new OnceProp(static fn () => 'Tony');
        self::assertSame('Tony', $prop->resolve());
    }

    public function testAsReturnsAlias(): void
    {
        $prop = (new OnceProp(static fn () => []))->as('plans_alias');
        self::assertSame('plans_alias', $prop->getAlias());
    }

    public function testAsReturnsSameInstance(): void
    {
        $prop = new OnceProp(static fn () => []);
        self::assertSame($prop, $prop->as('alias'));
    }

    public function testDefaultAliasIsNull(): void
    {
        $prop = new OnceProp(static fn () => []);
        self::assertNull($prop->getAlias());
    }

    public function testUntilWithDateTimeInterface(): void
    {
        $expiry = new \DateTimeImmutable('2030-01-01T00:00:00+00:00');
        $prop = (new OnceProp(static fn () => []))->until($expiry);
        self::assertSame($expiry, $prop->getExpiresAt());
    }

    public function testUntilWithIntSetsExpiresAtRelativeToNow(): void
    {
        $before = time();
        $prop = (new OnceProp(static fn () => []))->until(3600);
        $after = time();

        $ts = $prop->getExpiresAt()?->getTimestamp();
        self::assertNotNull($ts);
        self::assertGreaterThanOrEqual($before + 3600, $ts);
        self::assertLessThanOrEqual($after + 3600, $ts);
    }

    public function testDefaultExpiresAtIsNull(): void
    {
        $prop = new OnceProp(static fn () => []);
        self::assertNull($prop->getExpiresAt());
    }

    public function testFreshReturnsSameInstance(): void
    {
        $prop = new OnceProp(static fn () => []);
        self::assertSame($prop, $prop->fresh());
    }

    public function testFreshSetsFlag(): void
    {
        $prop = (new OnceProp(static fn () => []))->fresh();
        self::assertTrue($prop->isFresh());
    }

    public function testDefaultIsFreshIsFalse(): void
    {
        $prop = new OnceProp(static fn () => []);
        self::assertFalse($prop->isFresh());
    }
}
