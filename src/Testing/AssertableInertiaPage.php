<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Testing;

use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fluent DSL for asserting Inertia.js page objects in tests.
 *
 * Works with both XHR responses (JSON body) and HTML first-visit responses
 * (data-page attribute).
 *
 * Usage:
 *   AssertableInertiaPage::fromResponse($response)
 *       ->component('Users/Index')
 *       ->url('/users')
 *       ->has('users')
 *       ->where('users.0.name', 'John Doe')
 *       ->count('users', 5);
 */
final class AssertableInertiaPage
{
    /** @param array<string, mixed> $page */
    private function __construct(private readonly array $page)
    {
    }

    /**
     * Creates an AssertableInertiaPage from a Symfony HTTP response.
     *
     * Supports:
     *  - XHR responses (X-Inertia: true header) — parses JSON body
     *  - HTML first-visit responses — extracts data-page attribute
     */
    public static function fromResponse(Response $response): static
    {
        if ('true' === $response->headers->get('X-Inertia')) {
            /** @var array<string, mixed>|null $page */
            $page = json_decode((string) $response->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        } else {
            $content = (string) $response->getContent();

            if (1 !== preg_match("/data-page='([^']+)'/", $content, $matches)) {
                Assert::fail('No Inertia page data found in response. Expected an X-Inertia header or a data-page attribute.');
            }

            /** @var array<string, mixed>|null $page */
            $page = json_decode($matches[1], true, 512, \JSON_THROW_ON_ERROR);
        }

        if (!\is_array($page)) {
            Assert::fail('Inertia page data is not a valid JSON object.');
        }

        return new self($page);
    }

    /**
     * Asserts the Inertia component name.
     */
    public function component(string $expected): static
    {
        Assert::assertSame(
            $expected,
            $this->page['component'] ?? null,
            "Inertia component [{$expected}] does not match actual [{$this->page['component']}].",
        );

        return $this;
    }

    /**
     * Asserts the page URL (relative path + query string).
     */
    public function url(string $expected): static
    {
        Assert::assertSame(
            $expected,
            $this->page['url'] ?? null,
            "Inertia URL [{$expected}] does not match actual [{$this->page['url']}].",
        );

        return $this;
    }

    /**
     * Asserts the asset version string (or null).
     */
    public function version(?string $expected): static
    {
        Assert::assertSame(
            $expected,
            $this->page['version'] ?? null,
            'Inertia version does not match expected value.',
        );

        return $this;
    }

    /**
     * Asserts a prop key exists (dot notation supported, e.g. "user.address.city").
     *
     * A prop with a null value still "exists" — has() passes.
     */
    public function has(string $key): static
    {
        $this->assertPropExists($key);

        return $this;
    }

    /**
     * Asserts a prop key is absent (dot notation supported).
     */
    public function missing(string $key): static
    {
        $this->assertPropMissing($key);

        return $this;
    }

    /**
     * Asserts a prop value equals the expected value (dot notation supported).
     *
     * When $expected is a Closure, it receives the actual value for custom assertions.
     *
     * @param mixed|\Closure(mixed):void $expected
     */
    public function where(string $key, mixed $expected): static
    {
        $actual = $this->resolveProp($key);

        if ($expected instanceof \Closure) {
            $expected($actual);
        } else {
            Assert::assertSame(
                $expected,
                $actual,
                "Inertia prop [{$key}] does not match the expected value.",
            );
        }

        return $this;
    }

    /**
     * Asserts multiple prop key => value pairs.
     *
     * @param array<string, mixed> $props
     */
    public function whereAll(array $props): static
    {
        foreach ($props as $key => $value) {
            $this->where($key, $value);
        }

        return $this;
    }

    /**
     * Asserts a prop array has exactly N items (dot notation supported).
     */
    public function count(string $key, int $expected): static
    {
        $value = $this->resolveProp($key);

        Assert::assertIsArray($value, "Inertia prop [{$key}] is not an array.");
        Assert::assertCount($expected, $value, "Inertia prop [{$key}] does not have {$expected} item(s).");

        return $this;
    }

    /**
     * Dumps props for debugging (does not stop execution).
     */
    public function dump(): static
    {
        dump($this->page['props'] ?? $this->page);

        return $this;
    }

    /**
     * Dumps props and stops execution.
     */
    public function dd(): never // @codeCoverageIgnore
    {
        dd($this->page['props'] ?? $this->page); // @codeCoverageIgnore
    }

    // ── private ──────────────────────────────────────────────────────────────

    /**
     * Resolves a dot-notation key against props, returning the value.
     * Fails the test if any segment along the path is missing.
     */
    private function resolveProp(string $key): mixed
    {
        $segments = explode('.', $key);
        $value = $this->page['props'] ?? [];
        $path = '';

        foreach ($segments as $segment) {
            $path = '' === $path ? $segment : "{$path}.{$segment}";
            $arrayKey = is_numeric($segment) ? (int) $segment : $segment;

            if (!\is_array($value) || !\array_key_exists($arrayKey, $value)) {
                Assert::fail("Inertia prop [{$key}] is missing (failed at [{$path}]).");
            }

            $value = $value[$arrayKey];
        }

        return $value;
    }

    private function assertPropExists(string $key): void
    {
        $segments = explode('.', $key);
        $value = $this->page['props'] ?? [];
        $path = '';

        foreach ($segments as $segment) {
            $path = '' === $path ? $segment : "{$path}.{$segment}";
            $arrayKey = is_numeric($segment) ? (int) $segment : $segment;

            if (!\is_array($value)) {
                Assert::fail("Inertia prop [{$key}] is missing (failed at [{$path}]: parent is not an array).");
            }

            Assert::assertArrayHasKey($arrayKey, $value, "Inertia prop [{$key}] is missing (failed at [{$path}]).");
            $value = $value[$arrayKey];
        }
    }

    private function assertPropMissing(string $key): void
    {
        $segments = explode('.', $key);
        $value = $this->page['props'] ?? [];

        foreach ($segments as $index => $segment) {
            $arrayKey = is_numeric($segment) ? (int) $segment : $segment;

            if (!\is_array($value) || !\array_key_exists($arrayKey, $value)) {
                // path does not exist — key is missing as expected
                Assert::assertTrue(true);

                return;
            }

            if ($index === \count($segments) - 1) {
                // Last segment exists — should be missing
                Assert::assertArrayNotHasKey($arrayKey, $value, "Inertia prop [{$key}] is present but should be missing.");

                return;
            }

            $value = $value[$arrayKey];
        }

        Assert::fail("Inertia prop [{$key}] is present but should be missing."); // @codeCoverageIgnore
    }
}
