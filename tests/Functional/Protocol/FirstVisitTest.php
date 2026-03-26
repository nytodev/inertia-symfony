<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FirstVisitTest extends WebTestCase
{
    public function testFirstVisit_WithoutXInertiaHeader_ReturnsFullHtml(): void
    {
    }

    public function testFirstVisit_HtmlContainsDataPageAttribute(): void
    {
    }

    public function testFirstVisit_DataPageJsonIsProperlyEscaped(): void
    {
    }

    public function testFirstVisit_PageObjectContainsAllRequiredV2Fields(): void
    {
    }

    public function testFirstVisit_ClearHistoryAlwaysPresentInV2(): void
    {
    }

    public function testFirstVisit_EncryptHistoryAlwaysPresentInV2(): void
    {
    }
}
