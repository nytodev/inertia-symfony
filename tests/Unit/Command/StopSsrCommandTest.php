<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Command;

use Nytodev\InertiaBundle\Command\StopSsrCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class StopSsrCommandTest extends TestCase
{
    private string $ssrUrl = 'http://127.0.0.1:13714';

    public function testExecuteWhenServerResponds200ReturnsSuccess(): void
    {
        $client = new MockHttpClient(new MockResponse('OK', ['http_code' => 200]));
        $command = new StopSsrCommand($client, $this->ssrUrl);

        $application = new Application();
        $application->add($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('SSR server stopped', $tester->getDisplay());
    }

    public function testExecuteWhenServerThrowsExceptionReturnsFailure(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['error' => 'Connection refused']));
        $command = new StopSsrCommand($client, $this->ssrUrl);

        $application = new Application();
        $application->add($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Could not stop SSR server', $tester->getDisplay());
    }
}
