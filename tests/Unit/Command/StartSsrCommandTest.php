<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Command;

use Nytodev\InertiaBundle\Command\StartSsrCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

final class StartSsrCommandTest extends TestCase
{
    public function testExecuteWhenBundlePathNotFoundAndNoDetectionReturnsFailure(): void
    {
        // Pass a non-existent bundle path and a cwd where no auto-detected paths exist.
        $command = new StartSsrCommand('', '/nonexistent/cwd');

        $application = new Application();
        $application->addCommand($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('SSR bundle not found', $tester->getDisplay());
    }

    public function testExecuteWhenBundleConfiguredStartsProcess(): void
    {
        // Create a temporary fake bundle file so the path check passes.
        $tempDir = sys_get_temp_dir();
        $bundlePath = $tempDir.'/ssr.mjs';
        file_put_contents($bundlePath, '// fake SSR bundle');

        /** @var Process&MockObject $process */
        $process = $this->createMock(Process::class);
        $process->expects(self::once())->method('start');
        $process->method('isRunning')->willReturn(false);
        $process->method('getIncrementalOutput')->willReturn('');
        $process->method('getIncrementalErrorOutput')->willReturn('');

        $command = new StartSsrCommand($bundlePath, $tempDir, $process);

        $application = new Application();
        $application->addCommand($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        unlink($bundlePath);

        // Process stopped immediately (isRunning = false), so command exits with SUCCESS.
        self::assertSame(0, $exitCode);
    }
}
