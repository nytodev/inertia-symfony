<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Props;

use Nytodev\InertiaBundle\Props\OptionalProp;
use PHPUnit\Framework\TestCase;

final class OptionalPropTest extends TestCase
{
    public function testResolveInvokesCallback(): void
    {
        $invoked = false;
        $prop = new OptionalProp(static function () use (&$invoked) {
            $invoked = true;

            return 'value';
        });
        $prop->resolve();
        self::assertTrue($invoked);
    }

    public function testResolveReturnsCallbackReturnValue(): void
    {
        $prop = new OptionalProp(static fn () => 'Tony');
        self::assertSame('Tony', $prop->resolve());
    }

    // --- optional()->once() chaining ---

    public function testIsOnceDefaultsToFalse(): void
    {
        $prop = new OptionalProp(static fn () => []);
        self::assertFalse($prop->isOnce());
    }

    public function testOnceSetsIsOnceToTrue(): void
    {
        $prop = (new OptionalProp(static fn () => []))->once();
        self::assertTrue($prop->isOnce());
    }

    public function testOnceReturnsSameInstance(): void
    {
        $prop = new OptionalProp(static fn () => []);
        self::assertSame($prop, $prop->once());
    }
}
