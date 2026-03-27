<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Props;

use Nytodev\InertiaBundle\Props\DeferProp;
use PHPUnit\Framework\TestCase;

final class DeferPropTest extends TestCase
{
    public function testGetGroupWithDefaultGroupReturnsDefault(): void
    {
        $prop = new DeferProp(static fn () => []);
        self::assertSame('default', $prop->getGroup());
    }

    public function testGetGroupWithCustomGroupReturnsCustomGroup(): void
    {
        $prop = new DeferProp(static fn () => [], 'analytics');
        self::assertSame('analytics', $prop->getGroup());
    }

    public function testResolveInvokesCallback(): void
    {
        $invoked = false;
        $prop = new DeferProp(static function () use (&$invoked) {
            $invoked = true;

            return [];
        });
        $prop->resolve();
        self::assertTrue($invoked);
    }

    public function testResolveReturnsCallbackReturnValue(): void
    {
        $prop = new DeferProp(static fn () => ['a', 'b']);
        self::assertSame(['a', 'b'], $prop->resolve());
    }
}
