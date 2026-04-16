<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Service;

use Nytodev\InertiaBundle\Response\InertiaResponse;
use Nytodev\InertiaBundle\Service\Inertia;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * Tests for component file existence validation in Inertia::render().
 *
 * Mirrors Laravel's test_will_throw_exception_if_component_does_not_exist_when_ensuring_is_enabled
 * and test_will_not_throw_exception_if_component_does_not_exist_when_ensuring_is_disabled.
 */
final class InertiaComponentValidationTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        $this->fixturesDir = sys_get_temp_dir().'/inertia_test_pages_'.uniqid();
        mkdir($this->fixturesDir, 0o755, true);
        // Create a real component file for positive tests
        file_put_contents($this->fixturesDir.'/ExamplePage.vue', '<template><div/></template>');
        mkdir($this->fixturesDir.'/Users', 0o755, true);
        file_put_contents($this->fixturesDir.'/Users/Index.vue', '<template><div/></template>');
    }

    protected function tearDown(): void
    {
        @unlink($this->fixturesDir.'/ExamplePage.vue');
        @unlink($this->fixturesDir.'/Users/Index.vue');
        @rmdir($this->fixturesDir.'/Users');
        @rmdir($this->fixturesDir);
    }

    public function testRenderWhenEnsurePagesExistAndFileNotFoundThrowsInvalidArgumentException(): void
    {
        $inertia = $this->makeService(
            ensurePagesExist: true,
            pagePaths: [$this->fixturesDir],
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Inertia page component [NonExistent] not found.');

        $inertia->render('NonExistent');
    }

    public function testRenderWhenEnsurePagesExistAndFileFoundReturnsResponse(): void
    {
        $inertia = $this->makeService(
            ensurePagesExist: true,
            pagePaths: [$this->fixturesDir],
        );

        $response = $inertia->render('ExamplePage');

        self::assertInstanceOf(Response::class, $response);
    }

    public function testRenderWhenEnsurePagesExistAndFileFoundInSubdirReturnsResponse(): void
    {
        $inertia = $this->makeService(
            ensurePagesExist: true,
            pagePaths: [$this->fixturesDir],
        );

        $response = $inertia->render('Users/Index');

        self::assertInstanceOf(Response::class, $response);
    }

    public function testRenderWhenEnsureDisabledAndFileNotFoundReturnsResponse(): void
    {
        $inertia = $this->makeService(
            ensurePagesExist: false,
            pagePaths: [$this->fixturesDir],
        );

        $response = $inertia->render('NonExistent');

        self::assertInstanceOf(Response::class, $response);
    }

    public function testRenderWhenPathsEmptyAndEnsureEnabledSkipsValidation(): void
    {
        $inertia = $this->makeService(
            ensurePagesExist: true,
            pagePaths: [],
        );

        // No paths configured → validation skipped → no exception
        $response = $inertia->render('NonExistent');

        self::assertInstanceOf(Response::class, $response);
    }

    public function testRenderWhenExtensionMismatchAndEnsureEnabledThrows(): void
    {
        $inertia = $this->makeService(
            ensurePagesExist: true,
            pagePaths: [$this->fixturesDir],
            pageExtensions: ['jsx', 'tsx'], // .vue files won't match
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Inertia page component [ExamplePage] not found.');

        $inertia->render('ExamplePage');
    }

    public function testRenderWhenMultiplePathsAndFileInSecondPathReturnsResponse(): void
    {
        $otherDir = sys_get_temp_dir().'/inertia_test_other_'.uniqid();
        mkdir($otherDir, 0o755, true);

        $inertia = $this->makeService(
            ensurePagesExist: true,
            pagePaths: [$otherDir, $this->fixturesDir], // file only in second path
        );

        try {
            $response = $inertia->render('ExamplePage');
            self::assertInstanceOf(Response::class, $response);
        } finally {
            @rmdir($otherDir);
        }
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    /**
     * @param string[] $pagePaths
     * @param string[] $pageExtensions
     */
    private function makeService(
        bool $ensurePagesExist = false,
        array $pagePaths = [],
        array $pageExtensions = ['vue', 'jsx', 'tsx', 'js', 'ts', 'svelte'],
    ): Inertia {
        $request = Request::create('/test');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $twig = new Environment(new ArrayLoader([
            'base.html.twig' => '<body>{{ page|json_encode }}</body>',
        ]));
        $inertiaResponse = new InertiaResponse($twig, 'base.html.twig');

        return new Inertia(
            requestStack: $requestStack,
            inertiaResponse: $inertiaResponse,
            version: null,
            defaultEncryptHistory: false,
            exposeSharedPropKeys: false,
            ensurePagesExist: $ensurePagesExist,
            pagePaths: $pagePaths,
            pageExtensions: $pageExtensions,
        );
    }
}
