<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional;

use Nytodev\InertiaBundle\Props\AlwaysProp;
use Nytodev\InertiaBundle\Props\DeferProp;
use Nytodev\InertiaBundle\Props\LazyProp;
use Nytodev\InertiaBundle\Props\MergeProp;
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

    public function merge(): Response
    {
        return $this->inertia->render('TestComponent', [
            'merge_a' => new MergeProp(static fn () => [1, 2]),
            'merge_b' => new MergeProp(static fn () => [3, 4]),
        ]);
    }

    public function matchPropsOn(): Response
    {
        return $this->inertia->render('TestComponent', [
            'merge_a' => new MergeProp(static fn () => [['id' => 1], ['id' => 2]], matchOn: 'id'),
            'merge_b' => new MergeProp(static fn () => [['id' => 3], ['id' => 4]]),
        ]);
    }

    public function defer(): Response
    {
        return $this->inertia->render('TestComponent', [
            'eager' => 'eager-value',
            'deferred' => new DeferProp(static fn () => 'deferred-value'),
        ]);
    }

    public function always(): Response
    {
        return $this->inertia->render('TestComponent', [
            'always' => new AlwaysProp(static fn () => 'always-value'),
            'regular' => 'regular-value',
        ]);
    }

    public function clearHistory(): Response
    {
        $this->inertia->clearHistory();

        return $this->inertia->render('TestComponent', ['foo' => 'bar']);
    }

    public function encryptHistory(): Response
    {
        $this->inertia->encryptHistory();

        return $this->inertia->render('TestComponent', ['foo' => 'bar']);
    }
}
