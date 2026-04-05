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

    // --- merge flags ---

    public function testShouldMergeDefaultsToFalse(): void
    {
        $prop = new DeferProp(static fn () => []);
        self::assertFalse($prop->shouldMerge());
    }

    public function testMergeSetsShouldMergeToTrue(): void
    {
        $prop = (new DeferProp(static fn () => []))->merge();
        self::assertTrue($prop->shouldMerge());
    }

    public function testMergeReturnsSameInstance(): void
    {
        $prop = new DeferProp(static fn () => []);
        self::assertSame($prop, $prop->merge());
    }

    public function testIsDeepDefaultsToFalse(): void
    {
        $prop = new DeferProp(static fn () => []);
        self::assertFalse($prop->isDeep());
    }

    public function testDeepMergeSetsIsDeepAndShouldMerge(): void
    {
        $prop = (new DeferProp(static fn () => []))->deepMerge();
        self::assertTrue($prop->isDeep());
        self::assertTrue($prop->shouldMerge());
    }

    public function testIsPrependDefaultsToFalse(): void
    {
        $prop = new DeferProp(static fn () => []);
        self::assertFalse($prop->isPrepend());
    }

    public function testPrependSetsIsPrependAndShouldMerge(): void
    {
        $prop = (new DeferProp(static fn () => []))->prepend();
        self::assertTrue($prop->isPrepend());
        self::assertTrue($prop->shouldMerge());
    }

    public function testGetMatchOnDefaultsToEmptyArray(): void
    {
        $prop = new DeferProp(static fn () => []);
        self::assertSame([], $prop->getMatchOn());
    }

    public function testMatchOnStoresKey(): void
    {
        $prop = (new DeferProp(static fn () => []))->matchOn('id');
        self::assertSame(['id'], $prop->getMatchOn());
    }

    public function testMatchOnAcceptsArray(): void
    {
        $prop = (new DeferProp(static fn () => []))->matchOn(['id', 'slug']);
        self::assertSame(['id', 'slug'], $prop->getMatchOn());
    }

    public function testFluentChainingMergeMatchOn(): void
    {
        $prop = (new DeferProp(static fn () => []))->merge()->matchOn('id');
        self::assertTrue($prop->shouldMerge());
        self::assertSame(['id'], $prop->getMatchOn());
    }

    // --- path-specific merge ---

    public function testGetAppendsAtPathsByDefaultReturnsEmptyArray(): void
    {
        $prop = new DeferProp(static fn () => []);
        self::assertSame([], $prop->getAppendsAtPaths());
    }

    public function testAppendAtWithStringStoresSinglePath(): void
    {
        $prop = (new DeferProp(static fn () => []))->appendAt('data');
        self::assertSame(['data'], $prop->getAppendsAtPaths());
    }

    public function testAppendAtWithArrayStoresPaths(): void
    {
        $prop = (new DeferProp(static fn () => []))->appendAt(['data', 'items']);
        self::assertSame(['data', 'items'], $prop->getAppendsAtPaths());
    }

    public function testAppendAtEnablesMerge(): void
    {
        $prop = (new DeferProp(static fn () => []))->appendAt('data');
        self::assertTrue($prop->shouldMerge());
    }

    public function testAppendAtReturnsSameInstance(): void
    {
        $prop = new DeferProp(static fn () => []);
        self::assertSame($prop, $prop->appendAt('data'));
    }

    public function testGetPrependsAtPathsByDefaultReturnsEmptyArray(): void
    {
        $prop = new DeferProp(static fn () => []);
        self::assertSame([], $prop->getPrependsAtPaths());
    }

    public function testPrependAtWithStringStoresSinglePath(): void
    {
        $prop = (new DeferProp(static fn () => []))->prependAt('items');
        self::assertSame(['items'], $prop->getPrependsAtPaths());
    }

    public function testPrependAtEnablesMerge(): void
    {
        $prop = (new DeferProp(static fn () => []))->prependAt('items');
        self::assertTrue($prop->shouldMerge());
    }

    public function testMergesAtRootIsTrueWhenNoPaths(): void
    {
        $prop = (new DeferProp(static fn () => []))->merge();
        self::assertTrue($prop->mergesAtRoot());
    }

    public function testMergesAtRootIsFalseWhenAppendsAtPathsSet(): void
    {
        $prop = (new DeferProp(static fn () => []))->appendAt('data');
        self::assertFalse($prop->mergesAtRoot());
    }

    public function testMergesAtRootIsFalseWhenPrependsAtPathsSet(): void
    {
        $prop = (new DeferProp(static fn () => []))->prependAt('items');
        self::assertFalse($prop->mergesAtRoot());
    }

    // --- once flag ---

    public function testIsOnceDefaultsToFalse(): void
    {
        $prop = new DeferProp(static fn () => []);
        self::assertFalse($prop->isOnce());
    }

    public function testOnceSetsIsOnceToTrue(): void
    {
        $prop = (new DeferProp(static fn () => []))->once();
        self::assertTrue($prop->isOnce());
    }

    public function testOnceReturnsSameInstance(): void
    {
        $prop = new DeferProp(static fn () => []);
        self::assertSame($prop, $prop->once());
    }

    public function testFluentChainingOnceWithMerge(): void
    {
        $prop = (new DeferProp(static fn () => []))->once()->merge();
        self::assertTrue($prop->isOnce());
        self::assertTrue($prop->shouldMerge());
    }
}
