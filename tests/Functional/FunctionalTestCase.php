<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional;

use Nytodev\InertiaBundle\Testing\AssertableInertiaPage;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for functional tests.
 *
 * Restores PHP exception handlers after each test to prevent PHPUnit 11
 * from marking tests as risky due to Symfony's ErrorHandler registration.
 */
abstract class FunctionalTestCase extends WebTestCase
{
    /** @var list<callable> */
    private array $exceptionHandlersBefore = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->exceptionHandlersBefore = $this->captureActiveExceptionHandlers();
    }

    protected function tearDown(): void
    {
        $after = $this->captureActiveExceptionHandlers();
        $added = \count($after) - \count($this->exceptionHandlersBefore);
        for ($i = 0; $i < $added; ++$i) {
            restore_exception_handler();
        }
        parent::tearDown();
    }

    /**
     * Asserts that the response contains an Inertia page object matching the given callback.
     *
     * Works with both XHR (JSON) and HTML (first-visit) responses.
     *
     * @param callable(AssertableInertiaPage): void $callback
     */
    protected function assertInertia(Response $response, callable $callback): void
    {
        $callback(AssertableInertiaPage::fromResponse($response));
    }

    /**
     * Captures the currently active exception handler stack.
     *
     * @return list<callable>
     */
    private function captureActiveExceptionHandlers(): array
    {
        $handlers = [];

        while (true) {
            $previous = set_exception_handler(static fn (\Throwable $e) => null);
            restore_exception_handler();

            if (null === $previous) {
                break;
            }

            $handlers[] = $previous;
            restore_exception_handler();
        }

        $handlers = array_reverse($handlers);

        foreach ($handlers as $handler) {
            set_exception_handler($handler);
        }

        return $handlers;
    }
}
