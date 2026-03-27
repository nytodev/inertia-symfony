<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional;

use Nytodev\InertiaBundle\Props\LazyProp;
use Nytodev\InertiaBundle\Service\Inertia;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class TestController
{
    public function __construct(private readonly Inertia $inertia)
    {
    }

    public function index(): Response
    {
        return $this->inertia->render('TestComponent', ['foo' => 'bar']);
    }

    public function redirect(): Response
    {
        return new RedirectResponse('/test', 302);
    }

    public function shared(): Response
    {
        return $this->inertia->render('TestComponent', ['local' => 'value']);
    }

    public function partial(): Response
    {
        return $this->inertia->render('TestComponent', [
            'foo' => 'bar',
            'baz' => 'qux',
            'errors' => [],
        ]);
    }

    public function lazy(): Response
    {
        return $this->inertia->render('TestComponent', [
            'eager' => 'value',
            'lazy' => new LazyProp(static fn () => 'lazy-value'),
            'errors' => [],
        ]);
    }
}
