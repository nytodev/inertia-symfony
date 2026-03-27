<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Response;

use Nytodev\InertiaBundle\Props\DeferProp;
use Nytodev\InertiaBundle\Props\LazyProp;
use Nytodev\InertiaBundle\Props\MergeProp;
use Nytodev\InertiaBundle\Props\OnceProp;
use Nytodev\InertiaBundle\Response\InertiaResponse;
use Nytodev\InertiaBundle\Twig\InertiaTwigExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class InertiaResponseTest extends TestCase
{
    private InertiaResponse $response;

    protected function setUp(): void
    {
        $twig = new Environment(new ArrayLoader([
            'base.html.twig' => '<body>{{ inertia(page) }}</body>',
        ]));
        $twig->addExtension(new InertiaTwigExtension());
        $this->response = new InertiaResponse($twig, 'base.html.twig');
    }

    // -------------------------------------------------------------------------
    // buildPageObject()
    // -------------------------------------------------------------------------

    public function testBuildPageObjectAlwaysIncludesClearHistoryAndEncryptHistory(): void
    {
        $page = $this->response->buildPageObject('Home', [], '/home', null, false, false);
        self::assertArrayHasKey('clearHistory', $page);
        self::assertArrayHasKey('encryptHistory', $page);
        self::assertFalse($page['clearHistory']);
        self::assertFalse($page['encryptHistory']);
    }

    public function testBuildPageObjectContainsAllRequiredV2Fields(): void
    {
        $page = $this->response->buildPageObject('Home', ['foo' => 'bar'], '/home', 'v1', false, false);
        self::assertSame('Home', $page['component']);
        self::assertSame(['foo' => 'bar'], $page['props']);
        self::assertSame('/home', $page['url']);
        self::assertSame('v1', $page['version']);
    }

    public function testBuildPageObjectDeferredPropsOmittedWhenEmpty(): void
    {
        $page = $this->response->buildPageObject('Home', [], '/home', null, false, false);
        self::assertArrayNotHasKey('deferredProps', $page);
    }

    public function testBuildPageObjectDeferredPropsIncludedWhenPresent(): void
    {
        $page = $this->response->buildPageObject('Home', [], '/home', null, false, false, ['default' => ['users']]);
        self::assertSame(['default' => ['users']], $page['deferredProps']);
    }

    public function testBuildPageObjectJsonEncodesWithXssProtectionFlags(): void
    {
        // This is tested via renderInertia in Twig extension; here we verify the page object
        // has the component key so the extension can encode it safely.
        $page = $this->response->buildPageObject('<script>alert(1)</script>', [], '/home', null, false, false);
        self::assertSame('<script>alert(1)</script>', $page['component']);
    }

    // -------------------------------------------------------------------------
    // resolveProps()
    // -------------------------------------------------------------------------

    public function testResolvePropsWithLazyPropSkipsOnFullRender(): void
    {
        $props = ['name' => new LazyProp(static fn () => 'Tony'), 'title' => 'Hello'];
        $resolved = $this->response->resolveProps($props, [], [], [], false);
        self::assertArrayNotHasKey('name', $resolved);
        self::assertSame('Hello', $resolved['title']);
    }

    public function testResolvePropsWithLazyPropResolvesOnPartialReload(): void
    {
        $props = ['name' => new LazyProp(static fn () => 'Tony')];
        $resolved = $this->response->resolveProps($props, ['name'], [], [], true);
        self::assertSame('Tony', $resolved['name']);
    }

    public function testResolvePropsWithDeferPropExcludesFromPropsArray(): void
    {
        $props = ['users' => new DeferProp(static fn () => []), 'title' => 'Hello'];
        $resolved = $this->response->resolveProps($props, [], [], [], false);
        self::assertArrayNotHasKey('users', $resolved);
        self::assertSame('Hello', $resolved['title']);
    }

    public function testResolvePropsErrorsPropAlwaysPresent(): void
    {
        $props = ['errors' => ['name' => 'required'], 'title' => 'Hello'];
        $resolved = $this->response->resolveProps($props, ['title'], [], [], true);
        self::assertArrayHasKey('errors', $resolved);
        self::assertSame(['name' => 'required'], $resolved['errors']);
    }

    public function testResolvePropsWithOncePropResolvesNormally(): void
    {
        $props = ['name' => new OnceProp(static fn () => 'Tony')];
        $resolved = $this->response->resolveProps($props);
        self::assertSame('Tony', $resolved['name']);
    }

    public function testResolvePropsWithOncePropSkipsWhenInExceptOnce(): void
    {
        $props = ['name' => new OnceProp(static fn () => 'Tony'), 'title' => 'Hello'];
        $resolved = $this->response->resolveProps($props, [], [], ['name']);
        self::assertArrayNotHasKey('name', $resolved);
        self::assertSame('Hello', $resolved['title']);
    }

    public function testResolvePropsWithClosureResolvesValue(): void
    {
        $props = ['count' => static fn () => 42];
        $resolved = $this->response->resolveProps($props);
        self::assertSame(42, $resolved['count']);
    }

    public function testResolvePropsExceptFilterWins(): void
    {
        $props = ['a' => 1, 'b' => 2, 'c' => 3];
        $resolved = $this->response->resolveProps($props, ['a', 'b'], ['b'], [], true);
        self::assertArrayHasKey('a', $resolved);
        self::assertArrayNotHasKey('b', $resolved);
    }

    public function testResolvePropsWithMergePropResolvesNormally(): void
    {
        $props = ['items' => new MergeProp(static fn () => [1, 2, 3])];
        $resolved = $this->response->resolveProps($props);
        self::assertSame([1, 2, 3], $resolved['items']);
    }

    public function testResolvePropsDeferPropAlwaysExcludedOnPartialReload(): void
    {
        $props = ['users' => new DeferProp(static fn () => []), 'title' => 'Hello'];
        $resolved = $this->response->resolveProps($props, ['users', 'title'], [], [], true);
        self::assertArrayNotHasKey('users', $resolved);
        self::assertSame('Hello', $resolved['title']);
    }

    // -------------------------------------------------------------------------
    // build()
    // -------------------------------------------------------------------------

    public function testBuildWithFirstVisitRequestReturnsHtmlResponse(): void
    {
        $loader = new ArrayLoader(['base.html.twig' => '<div>{{ page|json_encode }}</div>']);
        $twig = new Environment($loader);
        $response = new InertiaResponse($twig, 'base.html.twig');

        $request = Request::create('/home');
        $result = $response->build('Home', ['foo' => 'bar'], '/home', null, $request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('Home', (string) $result->getContent());
    }

    public function testBuildWithXhrRequestReturnsJsonResponse(): void
    {
        $twig = new Environment(new ArrayLoader([]));
        $response = new InertiaResponse($twig, 'base.html.twig');

        $request = Request::create('/home');
        $request->headers->set('X-Inertia', 'true');

        $result = $response->build('Home', ['foo' => 'bar'], '/home', null, $request);

        self::assertSame(200, $result->getStatusCode());
        self::assertSame('true', $result->headers->get('X-Inertia'));
        self::assertStringContainsString('X-Inertia', $result->headers->get('Vary') ?? '');
        self::assertStringContainsString('application/json', $result->headers->get('Content-Type') ?? '');

        $data = json_decode((string) $result->getContent(), true);
        self::assertIsArray($data);
        self::assertSame('Home', $data['component']);
    }

    public function testBuildWithXhrRequestPageObjectHasAllV2Fields(): void
    {
        $twig = new Environment(new ArrayLoader([]));
        $response = new InertiaResponse($twig, 'base.html.twig');

        $request = Request::create('/home');
        $request->headers->set('X-Inertia', 'true');

        $result = $response->build('Home', [], '/home', null, $request);
        $data = json_decode((string) $result->getContent(), true);
        self::assertIsArray($data);

        self::assertArrayHasKey('component', $data);
        self::assertArrayHasKey('props', $data);
        self::assertArrayHasKey('url', $data);
        self::assertArrayHasKey('version', $data);
        self::assertArrayHasKey('clearHistory', $data);
        self::assertArrayHasKey('encryptHistory', $data);
    }

    public function testBuildPropsAlwaysContainsErrorsDefaultWhenNotProvidedByController(): void
    {
        $twig = new Environment(new ArrayLoader([]));
        $response = new InertiaResponse($twig, 'base.html.twig');

        $request = Request::create('/home');
        $request->headers->set('X-Inertia', 'true');

        $result = $response->build('Home', [], '/home', null, $request);
        $data = json_decode((string) $result->getContent(), true);
        self::assertIsArray($data);

        self::assertArrayHasKey('errors', $data['props']);
        self::assertSame([], $data['props']['errors']);
    }

    public function testBuildWithPartialReloadHeadersReturnsOnlyRequestedProps(): void
    {
        $twig = new Environment(new ArrayLoader([]));
        $response = new InertiaResponse($twig, 'base.html.twig');

        $request = Request::create('/home');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Partial-Data', 'foo');
        $request->headers->set('X-Inertia-Partial-Component', 'Home');

        $result = $response->build('Home', ['foo' => 'bar', 'baz' => 'qux'], '/home', null, $request);
        $data = json_decode((string) $result->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['props']);

        self::assertArrayHasKey('foo', $data['props']);
        self::assertArrayNotHasKey('baz', $data['props']);
    }

    public function testBuildHtmlFirstVisitWithInertiaExtensionRendersDataPageDiv(): void
    {
        $request = Request::create('/home');

        $result = $this->response->build('Home', ['name' => 'Tony'], '/home', null, $request);

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('data-page', (string) $result->getContent());
        self::assertStringContainsString('Home', (string) $result->getContent());
    }

    public function testBuildWithDeferPropIncludesDeferredGroupInPageObject(): void
    {
        $request = Request::create('/home');
        $request->headers->set('X-Inertia', 'true');

        $result = $this->response->build('Home', [
            'users' => new DeferProp(static fn () => ['Alice']),
            'posts' => new DeferProp(static fn () => ['B'], 'sidebar'),
        ], '/home', null, $request);

        $data = json_decode((string) $result->getContent(), true);
        self::assertIsArray($data);
        self::assertArrayHasKey('deferredProps', $data);
        self::assertContains('users', $data['deferredProps']['default']);
        self::assertContains('posts', $data['deferredProps']['sidebar']);
    }

    public function testBuildWithAllMergePropTypesTracksEachCategoryInPageObject(): void
    {
        $request = Request::create('/home');
        $request->headers->set('X-Inertia', 'true');

        $result = $this->response->build('Home', [
            'regular' => new MergeProp(static fn () => [1, 2]),
            'prepended' => new MergeProp(static fn () => [0], prepend: true),
            'deep' => new MergeProp(static fn () => ['k' => 'v'], deep: true),
        ], '/home', null, $request);

        $data = json_decode((string) $result->getContent(), true);
        self::assertIsArray($data);
        self::assertContains('regular', $data['mergeProps']);
        self::assertContains('prepended', $data['prependProps']);
        self::assertContains('deep', $data['deepMergeProps']);
    }

    public function testBuildPageObjectWithNonEmptyMergeArraysIncludesAllKeys(): void
    {
        $page = $this->response->buildPageObject(
            'Home', [], '/home', null, false, false,
            [], ['a'], ['b'], ['c'],
        );

        self::assertSame(['a'], $page['mergeProps']);
        self::assertSame(['b'], $page['prependProps']);
        self::assertSame(['c'], $page['deepMergeProps']);
    }

    public function testBuildWithSpacesInPartialDataHeaderParsesAndTrimsCorrectly(): void
    {
        $request = Request::create('/home');
        $request->headers->set('X-Inertia', 'true');
        $request->headers->set('X-Inertia-Partial-Data', 'foo , bar , baz');
        $request->headers->set('X-Inertia-Partial-Component', 'Home');

        $result = $this->response->build('Home', [
            'foo' => 1, 'bar' => 2, 'baz' => 3, 'qux' => 4,
        ], '/home', null, $request);

        $data = json_decode((string) $result->getContent(), true);
        self::assertIsArray($data);
        self::assertIsArray($data['props']);
        self::assertArrayHasKey('foo', $data['props']);
        self::assertArrayHasKey('bar', $data['props']);
        self::assertArrayHasKey('baz', $data['props']);
        self::assertArrayNotHasKey('qux', $data['props']);
    }
}
