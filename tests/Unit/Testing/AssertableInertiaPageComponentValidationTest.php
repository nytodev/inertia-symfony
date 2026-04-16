<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Testing;

use Nytodev\InertiaBundle\Testing\AssertableInertiaPage;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for component file existence validation in AssertableInertiaPage::component().
 *
 * Mirrors Laravel's AssertableInertiaTest tests for filesystem validation.
 */
final class AssertableInertiaPageComponentValidationTest extends TestCase
{
    private string $fixturesDir;

    protected function setUp(): void
    {
        // Reset static config before each test
        AssertableInertiaPage::configure(paths: [], extensions: ['vue', 'jsx', 'tsx', 'js', 'ts', 'svelte'], ensureExists: false);

        $this->fixturesDir = sys_get_temp_dir().'/inertia_assert_pages_'.uniqid();
        mkdir($this->fixturesDir, 0o755, true);
        file_put_contents($this->fixturesDir.'/ExamplePage.vue', '<template><div/></template>');
        mkdir($this->fixturesDir.'/Users', 0o755, true);
        file_put_contents($this->fixturesDir.'/Users/Index.vue', '<template><div/></template>');
    }

    protected function tearDown(): void
    {
        // Reset static config after each test
        AssertableInertiaPage::configure(paths: [], extensions: ['vue', 'jsx', 'tsx', 'js', 'ts', 'svelte'], ensureExists: false);

        @unlink($this->fixturesDir.'/ExamplePage.vue');
        @unlink($this->fixturesDir.'/Users/Index.vue');
        @rmdir($this->fixturesDir.'/Users');
        @rmdir($this->fixturesDir);
    }

    public function testComponentWhenEnsureExistsTrueAndFileFoundPasses(): void
    {
        AssertableInertiaPage::configure(
            paths: [$this->fixturesDir],
            ensureExists: true,
        );

        $page = AssertableInertiaPage::fromResponse($this->makeXhrResponse('ExamplePage'));

        // No exception thrown
        $page->component('ExamplePage');
        $this->addToAssertionCount(1);
    }

    public function testComponentWhenEnsureExistsTrueAndFileNotFoundFails(): void
    {
        AssertableInertiaPage::configure(
            paths: [$this->fixturesDir],
            ensureExists: true,
        );

        $page = AssertableInertiaPage::fromResponse($this->makeXhrResponse('NonExistent'));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Inertia page component file [NonExistent] does not exist.');

        $page->component('NonExistent');
    }

    public function testComponentWhenEnsureExistsFalseAndFileNotFoundSkipsCheck(): void
    {
        AssertableInertiaPage::configure(
            paths: [$this->fixturesDir],
            ensureExists: false,
        );

        $page = AssertableInertiaPage::fromResponse($this->makeXhrResponse('NonExistent'));

        // No exception — check skipped
        $page->component('NonExistent');
        $this->addToAssertionCount(1);
    }

    public function testComponentWhenPathsEmptyAndEnsureEnabledSkipsCheck(): void
    {
        AssertableInertiaPage::configure(
            paths: [],
            ensureExists: true,
        );

        $page = AssertableInertiaPage::fromResponse($this->makeXhrResponse('NonExistent'));

        // No paths → skip
        $page->component('NonExistent');
        $this->addToAssertionCount(1);
    }

    public function testComponentWhenForceTrueOverridesConfigFalse(): void
    {
        AssertableInertiaPage::configure(
            paths: [$this->fixturesDir],
            ensureExists: false, // globally disabled
        );

        $page = AssertableInertiaPage::fromResponse($this->makeXhrResponse('NonExistent'));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Inertia page component file [NonExistent] does not exist.');

        $page->component('NonExistent', true); // force-enable per call
    }

    public function testComponentWhenForceFalseOverridesConfigTrue(): void
    {
        AssertableInertiaPage::configure(
            paths: [$this->fixturesDir],
            ensureExists: true, // globally enabled
        );

        $page = AssertableInertiaPage::fromResponse($this->makeXhrResponse('NonExistent'));

        // Force-disable: no exception
        $page->component('NonExistent', false);
        $this->addToAssertionCount(1);
    }

    public function testComponentWhenExtensionMismatchAndEnsureEnabledFails(): void
    {
        AssertableInertiaPage::configure(
            paths: [$this->fixturesDir],
            extensions: ['jsx', 'tsx'], // .vue won't match
            ensureExists: true,
        );

        $page = AssertableInertiaPage::fromResponse($this->makeXhrResponse('ExamplePage'));

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Inertia page component file [ExamplePage] does not exist.');

        $page->component('ExamplePage');
    }

    public function testComponentWhenSubdirComponentAndFileFoundPasses(): void
    {
        AssertableInertiaPage::configure(
            paths: [$this->fixturesDir],
            ensureExists: true,
        );

        $page = AssertableInertiaPage::fromResponse($this->makeXhrResponse('Users/Index'));
        $page->component('Users/Index');
        $this->addToAssertionCount(1);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function makeXhrResponse(string $component): Response
    {
        $pageObject = json_encode([
            'component' => $component,
            'props' => [],
            'url' => '/test',
            'version' => null,
        ]);

        return new Response(
            (string) $pageObject,
            200,
            [
                'Content-Type' => 'application/json',
                'X-Inertia' => 'true',
            ],
        );
    }
}
