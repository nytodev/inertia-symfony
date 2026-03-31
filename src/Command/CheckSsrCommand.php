<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'inertia:check-ssr',
    description: 'Check whether the Inertia.js SSR server is running.',
)]
final class CheckSsrCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $ssrUrl,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $response = $this->httpClient->request('GET', rtrim($this->ssrUrl, '/').'/health', [
                'timeout' => 3,
            ]);

            if (200 === $response->getStatusCode()) {
                $output->writeln('<info>SSR server is running at '.$this->ssrUrl.'</info>');

                return Command::SUCCESS;
            }

            $output->writeln('<error>SSR server is not running at '.$this->ssrUrl.'</error>');

            return Command::FAILURE;
        } catch (\Throwable) {
            $output->writeln('<error>SSR server is not running at '.$this->ssrUrl.'</error>');

            return Command::FAILURE;
        }
    }
}
