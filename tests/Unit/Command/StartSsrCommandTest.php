<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Command;

use Nytodev\InertiaBundle\Command\StartSsrCommand;
use Nytodev\InertiaBundle\Ssr\BundleDetectorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

final class StartSsrCommandTest extends TestCase
{
    public function testExecuteWhenBundlePathNotFoundAndNoDetectionReturnsFailure(): void
    {
        /** @var BundleDetectorInterface&MockObject $detector */
        $detector = $this->createMock(BundleDetectorInterface::class);
        $detector->method('detect')->willReturn(null);

        $command = new StartSsrCommand($detector);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('SSR bundle not found', $tester->getDisplay());
    }

    public function testExecuteWhenBundleConfiguredStartsProcess(): void
    {
        $tempDir = sys_get_temp_dir();
        $bundlePath = $tempDir.'/ssr.mjs';
        file_put_contents($bundlePath, '// fake SSR bundle');

        /** @var BundleDetectorInterface&MockObject $detector */
        $detector = $this->createMock(BundleDetectorInterface::class);
        $detector->method('detect')->willReturn($bundlePath);

        /** @var Process&MockObject $process */
        $process = $this->createMock(Process::class);
        $process->expects(self::once())->method('start');
        $process->method('isRunning')->willReturn(false);
        $process->method('getIncrementalOutput')->willReturn('');
        $process->method('getIncrementalErrorOutput')->willReturn('');

        $command = new StartSsrCommand($detector, $process);

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

        /** @var BundleDetectorInterface&MockObject $detector */
        $detector = $this->createMock(BundleDetectorInterface::class);
        $detector->method('detect')->willReturn($bundlePath);

        /** @var Process&MockObject $process */
        $process = $this->createMock(Process::class);
        $process->expects(self::once())->method('start');

        // First call to isRunning returns true (loop body runs), second returns false (loop exits).
        $process->method('isRunning')->willReturnOnConsecutiveCalls(true, false);
        $process->method('getIncrementalOutput')->willReturn('SSR server started');
        $process->method('getIncrementalErrorOutput')->willReturn('some warning');

        $command = new StartSsrCommand($detector, $process);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        unlink($bundlePath);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('SSR server started', $tester->getDisplay());
        self::assertStringContainsString('some warning', $tester->getDisplay());
    }

    public function testExecuteWithDetectablePathStartsProcess(): void
    {
        $bundlePath = sys_get_temp_dir().'/ssr-auto-detect.mjs';
        file_put_contents($bundlePath, '// fake SSR bundle');

        /** @var BundleDetectorInterface&MockObject $detector */
        $detector = $this->createMock(BundleDetectorInterface::class);
        $detector->method('detect')->willReturn($bundlePath);

        /** @var Process&MockObject $process */
        $process = $this->createMock(Process::class);
        $process->expects(self::once())->method('start');
        $process->method('isRunning')->willReturn(false);
        $process->method('getIncrementalOutput')->willReturn('');
        $process->method('getIncrementalErrorOutput')->willReturn('');

        $command = new StartSsrCommand($detector, $process);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        unlink($bundlePath);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Starting SSR server', $tester->getDisplay());
    }

    public function testExecuteWhenDetectorReturnsNullReturnsFailure(): void
    {
        /** @var BundleDetectorInterface&MockObject $detector */
        $detector = $this->createMock(BundleDetectorInterface::class);
        $detector->method('detect')->willReturn(null);

        $command = new StartSsrCommand($detector);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('SSR bundle not found', $tester->getDisplay());
    }
}
