<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Props;

use Nytodev\InertiaBundle\Props\LazyProp;
use PHPUnit\Framework\TestCase;

final class LazyPropTest extends TestCase
{
    public function testResolveInvokesCallback(): void
    {
        $invoked = false;
        $prop = new LazyProp(static function () use (&$invoked) {
            $invoked = true;

            return 'value';
        });
        $prop->resolve();
        self::assertTrue($invoked);
    }

    public function testResolveReturnsCallbackReturnValue(): void
    {
        $prop = new LazyProp(static fn () => 'Tony');
        self::assertSame('Tony', $prop->resolve());
    }
}
