<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AssetVersionTest extends WebTestCase
{
    public function testAssetVersion_WhenVersionMatches_Returns200(): void
    {
    }

    public function testAssetVersion_WhenVersionMismatches_Returns409Conflict(): void
    {
    }

    public function testAssetVersion_On409_HasXInertiaLocationHeader(): void
    {
    }

    public function testAssetVersion_VersionCheckOnlyForGetRequests(): void
    {
    }

    public function testAssetVersion_On409_ReflashesSessionFlashData(): void
    {
    }

    public function testAssetVersion_WhenVersionIsNull_NoVersionCheck(): void
    {
    }
}
