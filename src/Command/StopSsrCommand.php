<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'inertia:stop-ssr',
    description: 'Stop the Inertia.js SSR server.',
)]
final class StopSsrCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $ssrUrl,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $response = $this->httpClient->request('GET', rtrim($this->ssrUrl, '/').'/shutdown', [
                'timeout' => 3,
            ]);

            // Trigger response to detect transport errors early.
            $response->getStatusCode();

            $io->success('SSR server stopped successfully.');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error('Could not stop SSR server at '.$this->ssrUrl.': '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
