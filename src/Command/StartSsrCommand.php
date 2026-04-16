<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Command;

use Nytodev\InertiaBundle\Ssr\BundleDetectorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'inertia:start-ssr',
    description: 'Start the Inertia.js SSR server.',
)]
final class StartSsrCommand extends Command
{
    public function __construct(
        private readonly BundleDetectorInterface $bundleDetector,
        private readonly ?Process $process = null,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bundlePath = $this->bundleDetector->detect();

        if (null === $bundlePath) {
            $output->writeln('<error>SSR bundle not found. Configure inertia.ssr_bundle in your bundle config.</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Starting SSR server with bundle: '.$bundlePath.'</info>');

        $process = $this->process ?? new Process(['node', $bundlePath]);
        $process->start();

        while ($process->isRunning()) {
            $out = $process->getIncrementalOutput();
            if ('' !== $out) {
                $output->write($out);
            }

            $err = $process->getIncrementalErrorOutput();
            if ('' !== $err) {
                $output->write('<error>'.$err.'</error>');
            }

            usleep(100_000);
        }

        return Command::SUCCESS;
    }
}
