<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Props;

use Nytodev\InertiaBundle\Props\ScrollProp;
use PHPUnit\Framework\TestCase;

final class ScrollPropTest extends TestCase
{
    public function testResolveInvokesCallback(): void
    {
        $invoked = false;
        $prop = new ScrollProp(static function () use (&$invoked) {
            $invoked = true;

            return [1, 2, 3];
        });
        $prop->resolve();
        self::assertTrue($invoked);
    }

    public function testResolveReturnsCallbackReturnValue(): void
    {
        $prop = new ScrollProp(static fn () => ['post1', 'post2']);
        self::assertSame(['post1', 'post2'], $prop->resolve());
    }

    public function testGetPageNameReturnsDefaultPage(): void
    {
        $prop = new ScrollProp(static fn () => []);
        self::assertSame('page', $prop->getPageName());
    }

    public function testGetPageNameReturnsCustomName(): void
    {
        $prop = new ScrollProp(static fn () => [], pageName: 'cursor');
        self::assertSame('cursor', $prop->getPageName());
    }

    public function testGetNextPageDefaultsToNull(): void
    {
        $prop = new ScrollProp(static fn () => []);
        self::assertNull($prop->getNextPage());
    }

    public function testGetNextPageReturnsValue(): void
    {
        $prop = new ScrollProp(static fn () => [], nextPage: 3);
        self::assertSame(3, $prop->getNextPage());
    }

    public function testGetPreviousPageDefaultsToNull(): void
    {
        $prop = new ScrollProp(static fn () => []);
        self::assertNull($prop->getPreviousPage());
    }

    public function testGetPreviousPageReturnsValue(): void
    {
        $prop = new ScrollProp(static fn () => [], previousPage: 1);
        self::assertSame(1, $prop->getPreviousPage());
    }

    public function testGetCurrentPageDefaultsToNull(): void
    {
        $prop = new ScrollProp(static fn () => []);
        self::assertNull($prop->getCurrentPage());
    }

    public function testGetCurrentPageReturnsValue(): void
    {
        $prop = new ScrollProp(static fn () => [], currentPage: 2);
        self::assertSame(2, $prop->getCurrentPage());
    }

    public function testIsPrependDefaultsToFalse(): void
    {
        $prop = new ScrollProp(static fn () => []);
        self::assertFalse($prop->isPrepend());
    }

    public function testIsPrependWhenTrueReturnsTrue(): void
    {
        $prop = new ScrollProp(static fn () => [], prepend: true);
        self::assertTrue($prop->isPrepend());
    }

    public function testToMetadataAlwaysIncludesPageName(): void
    {
        $prop = new ScrollProp(static fn () => [], pageName: 'p');
        $meta = $prop->toMetadata();
        self::assertArrayHasKey('pageName', $meta);
        self::assertSame('p', $meta['pageName']);
    }

    public function testToMetadataIncludesNullPaginationFields(): void
    {
        $prop = new ScrollProp(static fn () => []);
        $meta = $prop->toMetadata();
        self::assertNull($meta['nextPage']);
        self::assertNull($meta['previousPage']);
        self::assertNull($meta['currentPage']);
    }

    public function testToMetadataIncludesNonNullFields(): void
    {
        $prop = new ScrollProp(
            static fn () => [],
            pageName: 'page',
            nextPage: 2,
            previousPage: 1,
            currentPage: 1,
        );
        $meta = $prop->toMetadata();
        self::assertSame('page', $meta['pageName']);
        self::assertSame(2, $meta['nextPage']);
        self::assertSame(1, $meta['previousPage']);
        self::assertSame(1, $meta['currentPage']);
    }

    public function testToMetadataWithOnlyNextPageIncludesAllFieldsWithNullsForUnset(): void
    {
        $prop = new ScrollProp(static fn () => [], pageName: 'page', nextPage: 2);
        $meta = $prop->toMetadata();
        self::assertSame(['pageName' => 'page', 'previousPage' => null, 'nextPage' => 2, 'currentPage' => null], $meta);
    }

    public function testGetNextPageAcceptsStringCursor(): void
    {
        $prop = new ScrollProp(static fn () => [], nextPage: 'abc123');
        self::assertSame('abc123', $prop->getNextPage());
    }
}
