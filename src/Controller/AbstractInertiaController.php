<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Controller;

use Nytodev\InertiaBundle\Service\Inertia;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Convenience base controller for Inertia-driven controllers.
 * Not final — intended to be extended by application controllers.
 *
 * Usage:
 *   class MyController extends AbstractInertiaController
 *   {
 *       public function index(): Response
 *       {
 *           return $this->renderInertia('MyPage', ['data' => 'value']);
 *       }
 *   }
 */
abstract class AbstractInertiaController extends AbstractController
{
    public function __construct(
        protected readonly Inertia $inertia,
    ) {
    }

    /**
     * Render an Inertia component. Named renderInertia() to avoid shadowing
     * AbstractController::render() which returns a full Twig HTML response.
     *
     * @param array<string, mixed>      $props
     * @param array<string, mixed>|null $serializationContext When non-null, props are normalized via
     *                                                        Symfony Serializer after all prop types are resolved.
     *                                                        Requires symfony/serializer to be installed.
     */
    protected function renderInertia(string $component, array $props = [], ?array $serializationContext = null): Response
    {
        return $this->inertia->render($component, $props, $serializationContext);
    }
}
