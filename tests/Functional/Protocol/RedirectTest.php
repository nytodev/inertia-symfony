<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RedirectTest extends WebTestCase
{
    public function testRedirect_After302OnGet_Remains302(): void
    {
    }

    public function testRedirect_After302OnPut_ConvertedTo303(): void
    {
    }

    public function testRedirect_After302OnPatch_ConvertedTo303(): void
    {
    }

    public function testRedirect_After302OnDelete_ConvertedTo303(): void
    {
    }
}
