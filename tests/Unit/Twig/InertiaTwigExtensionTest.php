<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Twig;

use Nytodev\InertiaBundle\Twig\InertiaTwigExtension;
use PHPUnit\Framework\TestCase;

final class InertiaTwigExtensionTest extends TestCase
{
    private InertiaTwigExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new InertiaTwigExtension();
    }

    public function testRenderInertiaWithPageDataRendersDiv(): void
    {
        $html = $this->ext->renderInertia(['component' => 'Home', 'props' => []]);
        self::assertStringContainsString('<div id="app"', $html);
        self::assertStringContainsString('data-page=', $html);
    }

    public function testRenderInertiaJsonEscapesXssChars(): void
    {
        $html = $this->ext->renderInertia(['component' => '<script>', 'props' => []]);
        self::assertStringNotContainsString('<script>', $html);
    }

    public function testRenderInertiaHeadReturnsEmptyString(): void
    {
        self::assertSame('', $this->ext->renderInertiaHead(['component' => 'Home', 'props' => []]));
    }

    public function testGetFunctionsReturnsTwoFunctions(): void
    {
        $functions = $this->ext->getFunctions();
        self::assertCount(2, $functions);
    }
}
