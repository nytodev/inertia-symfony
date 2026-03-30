<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Props;

use Nytodev\InertiaBundle\Props\MergeProp;
use PHPUnit\Framework\TestCase;

final class MergePropTest extends TestCase
{
    public function testIsPrependWithDefaultValueReturnsFalse(): void
    {
        $prop = new MergeProp(static fn () => []);
        self::assertFalse($prop->isPrepend());
    }

    public function testIsPrependWhenTrueReturnsTrue(): void
    {
        $prop = new MergeProp(static fn () => [], prepend: true);
        self::assertTrue($prop->isPrepend());
    }

    public function testIsDeepWithDefaultValueReturnsFalse(): void
    {
        $prop = new MergeProp(static fn () => []);
        self::assertFalse($prop->isDeep());
    }

    public function testIsDeepWhenTrueReturnsTrue(): void
    {
        $prop = new MergeProp(static fn () => [], deep: true);
        self::assertTrue($prop->isDeep());
    }

    public function testResolveInvokesCallback(): void
    {
        $invoked = false;
        $prop = new MergeProp(static function () use (&$invoked) {
            $invoked = true;

            return [];
        });
        $prop->resolve();
        self::assertTrue($invoked);
    }

    public function testResolveReturnsCallbackReturnValue(): void
    {
        $prop = new MergeProp(static fn () => [1, 2, 3]);
        self::assertSame([1, 2, 3], $prop->resolve());
    }

    public function testGetMatchOnByDefaultReturnsEmptyArray(): void
    {
        $prop = new MergeProp(static fn () => []);
        self::assertSame([], $prop->getMatchOn());
    }

    public function testGetMatchOnWithStringReturnsNormalizedArray(): void
    {
        $prop = new MergeProp(static fn () => [], matchOn: 'id');
        self::assertSame(['id'], $prop->getMatchOn());
    }

    public function testGetMatchOnWithArrayReturnsArray(): void
    {
        $prop = new MergeProp(static fn () => [], matchOn: ['id', 'uuid']);
        self::assertSame(['id', 'uuid'], $prop->getMatchOn());
    }
}
