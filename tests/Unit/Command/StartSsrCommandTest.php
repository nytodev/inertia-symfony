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
        $application->add($command);

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
        $application->add($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        unlink($bundlePath);

        // Process stopped immediately (isRunning = false), so command exits with SUCCESS.
        self::assertSame(0, $exitCode);
    }

    public function testExecuteWithProcessOutputAndErrorWritesOutputAndReturnsSuccess(): void
    {
        $tempDir = sys_get_temp_dir();
        $bundlePath = $tempDir.'/ssr-output.mjs';
        file_put_contents($bundlePath, '// fake SSR bundle');

        /** @var Process&MockObject $process */
        $process = $this->createMock(Process::class);
        $process->expects(self::once())->method('start');

        // First call to isRunning returns true (loop body runs), second returns false (loop exits).
        $process->method('isRunning')->willReturnOnConsecutiveCalls(true, false);
        $process->method('getIncrementalOutput')->willReturn('SSR server started');
        $process->method('getIncrementalErrorOutput')->willReturn('some warning');

        $command = new StartSsrCommand($bundlePath, $tempDir, $process);

        $application = new Application();
        $application->add($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        unlink($bundlePath);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('SSR server started', $tester->getDisplay());
        self::assertStringContainsString('some warning', $tester->getDisplay());
    }

    public function testExecuteWithNullBundleAndDetectablePathInCwdStartsProcess(): void
    {
        // Create a fake SSR bundle at one of the auto-detected paths inside a temp dir.
        $tempDir = sys_get_temp_dir().'/inertia-ssr-detect-test-'.uniqid();
        $ssrDir = $tempDir.'/bootstrap/ssr';
        mkdir($ssrDir, 0777, true);
        $bundlePath = $ssrDir.'/ssr.mjs';
        file_put_contents($bundlePath, '// fake SSR bundle');

        /** @var Process&MockObject $process */
        $process = $this->createMock(Process::class);
        $process->expects(self::once())->method('start');
        $process->method('isRunning')->willReturn(false);
        $process->method('getIncrementalOutput')->willReturn('');
        $process->method('getIncrementalErrorOutput')->willReturn('');

        // ssrBundle = null triggers auto-detection; cwd = $tempDir so paths resolve correctly.
        $command = new StartSsrCommand(null, $tempDir, $process);

        $application = new Application();
        $application->add($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        unlink($bundlePath);
        rmdir($ssrDir);
        rmdir($tempDir.'/bootstrap');
        rmdir($tempDir);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Starting SSR server', $tester->getDisplay());
    }

    public function testExecuteWithNullBundleAndNoCwdAndNoDetectablePathReturnsFailure(): void
    {
        // ssrBundle = null, cwd = '' (falls back to getcwd()), but no DETECT_PATHS files exist.
        // We provide a cwd where no ssr files are present.
        $tempDir = sys_get_temp_dir().'/inertia-ssr-empty-'.uniqid();
        mkdir($tempDir, 0777, true);

        $command = new StartSsrCommand(null, $tempDir);

        $application = new Application();
        $application->add($command);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        rmdir($tempDir);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('SSR bundle not found', $tester->getDisplay());
    }
}
