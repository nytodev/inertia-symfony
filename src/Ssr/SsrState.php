<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Ssr;

use Symfony\Contracts\Service\ResetInterface;

/**
 * Request-scoped cache for the SSR dispatch result.
 * Ensures the gateway is only called once per request even when
 * both the Twig `inertia()` and `inertiaHead()` functions render.
 *
 * Tagged with kernel.reset so FrankenPHP / ReactPHP workers clear state
 * between requests automatically.
 */
final class SsrState implements ResetInterface
{
    /** @var array<string, mixed> */
    private array $page = [];

    private bool $dispatched = false;

    private ?SsrResponse $response = null;

    public function __construct(
        private readonly SsrGatewayInterface $gateway,
    ) {
    }

    /**
     * @param array<string, mixed> $page
     */
    public function setPage(array $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function dispatch(): ?SsrResponse
    {
        if (!$this->dispatched) {
            $this->dispatched = true;
            $this->response = $this->gateway->dispatch($this->page);
        }

        return $this->response;
    }

    public function reset(): void
    {
        $this->page = [];
        $this->dispatched = false;
        $this->response = null;
    }
}
