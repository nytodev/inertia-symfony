<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Command;

use Nytodev\InertiaBundle\Command\CheckSsrCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CheckSsrCommandTest extends TestCase
{
    private string $ssrUrl = 'http://127.0.0.1:13714';

    public function testExecuteWhenServerResponds200ReturnsSuccessWithMessage(): void
    {
        $client = new MockHttpClient(new MockResponse('OK', ['http_code' => 200]));
        $command = new CheckSsrCommand($client, $this->ssrUrl);

        $application = new Application();
        $application->addCommand($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('SSR server is running at', $tester->getDisplay());
        self::assertStringContainsString($this->ssrUrl, $tester->getDisplay());
    }

    public function testExecuteWhenServerRespondsNon200ReturnsFailure(): void
    {
        $client = new MockHttpClient(new MockResponse('Server Error', ['http_code' => 500]));
        $command = new CheckSsrCommand($client, $this->ssrUrl);

        $application = new Application();
        $application->addCommand($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('SSR server is not running at', $tester->getDisplay());
        self::assertStringContainsString($this->ssrUrl, $tester->getDisplay());
    }

    public function testExecuteWhenServerIsUnreachableReturnsFailure(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['error' => 'Connection refused']));
        $command = new CheckSsrCommand($client, $this->ssrUrl);

        $application = new Application();
        $application->addCommand($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('SSR server is not running at', $tester->getDisplay());
    }
}
