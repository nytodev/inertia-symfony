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
}
