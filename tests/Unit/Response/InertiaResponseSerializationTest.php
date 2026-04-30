<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Response;

use Nytodev\InertiaBundle\Response\InertiaResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;

/**
 * Tests optional Symfony Serializer support in InertiaResponse::build().
 *
 * Serialization is opt-in: it only runs when a non-null $serializationContext
 * is passed to build(). The serializer operates on the full page object AFTER
 * all prop types are resolved. When the serializer service is unavailable and a
 * context is passed, a LogicException is thrown.
 */
final class InertiaResponseSerializationTest extends TestCase
{
    /** @var Environment&MockObject */
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->twig->method('render')->willReturn('<html></html>');
    }

    public function testBuildWithNullContextAndNoSerializerPropsPassThroughUnchanged(): void
    {
        $response = new InertiaResponse($this->twig, 'base.html.twig');
        $request = Request::create('/test');

        $result = $response->build(
            component: 'Users/Index',
            props: ['name' => 'Alice'],
            url: '/test',
            version: null,
            request: $request,
            serializationContext: null,
        );

        self::assertSame(200, $result->getStatusCode());
    }

    public function testBuildWithContextButNoSerializerThrowsLogicException(): void
    {
        $response = new InertiaResponse($this->twig, 'base.html.twig');
        $request = Request::create('/test');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageMatches('/symfony\/serializer/i');

        $response->build(
            component: 'Users/Index',
            props: ['name' => 'Alice'],
            url: '/test',
            version: null,
            request: $request,
            serializationContext: ['groups' => ['api']],
        );
    }

    public function testBuildWithSerializerAndContextCallsSerializeOnFullPageObject(): void
    {
        /** @var SerializerInterface&MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $serializer
            ->expects(self::once())
            ->method('serialize')
            ->with(
                self::callback(static function (mixed $page): bool {
                    // Full page object is passed — must have component, props, url keys
                    return \is_array($page)
                        && isset($page['component'], $page['props'], $page['url'])
                        && 'Users/Index' === $page['component'];
                }),
                'json',
                self::callback(static fn (array $ctx): bool => isset($ctx['groups']) && ['api'] === $ctx['groups']),
            )
            ->willReturn('{"component":"Users/Index","props":{"name":"Alice","errors":{}},"url":"/test","version":null}');

        $response = new InertiaResponse($this->twig, 'base.html.twig', $serializer);
        $request = Request::create('/test');

        $result = $response->build(
            component: 'Users/Index',
            props: ['name' => 'Alice'],
            url: '/test',
            version: null,
            request: $request,
            serializationContext: ['groups' => ['api']],
        );

        self::assertSame(200, $result->getStatusCode());
    }

    public function testBuildWithSerializerAndContextSerializedValuesAppearInJsonResponse(): void
    {
        /** @var SerializerInterface&MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $serializer
            ->method('serialize')
            ->willReturn('{"component":"Users/Index","props":{"name":"Alice serialized","errors":{}},"url":"/test","version":null}');

        $response = new InertiaResponse($this->twig, 'base.html.twig', $serializer);

        $request = Request::create('/test');
        $request->headers->set('X-Inertia', 'true');

        $result = $response->build(
            component: 'Users/Index',
            props: ['name' => 'Alice'],
            url: '/test',
            version: null,
            request: $request,
            serializationContext: ['groups' => ['api']],
        );

        self::assertSame(200, $result->getStatusCode());
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $result->getContent(), true);
        self::assertSame('Alice serialized', $data['props']['name']);
    }

    public function testBuildWithSerializerButNullContextSerializerNotCalled(): void
    {
        /** @var SerializerInterface&MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $serializer
            ->expects(self::never())
            ->method('serialize');

        $response = new InertiaResponse($this->twig, 'base.html.twig', $serializer);
        $request = Request::create('/test');

        $result = $response->build(
            component: 'Users/Index',
            props: ['name' => 'Alice'],
            url: '/test',
            version: null,
            request: $request,
            serializationContext: null,
        );

        self::assertSame(200, $result->getStatusCode());
    }

    public function testSerializeDefaultContextMergedWithUserContext(): void
    {
        /** @var SerializerInterface&MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $serializer
            ->expects(self::once())
            ->method('serialize')
            ->with(
                self::anything(),
                'json',
                self::callback(static function (array $ctx): bool {
                    // Defaults must be present
                    return isset($ctx['json_encode_options'])
                        && isset($ctx['circular_reference_handler'])
                        && isset($ctx['preserve_empty_objects'])
                        && isset($ctx['enable_max_depth'])
                        // User context is merged on top
                        && isset($ctx['groups']) && ['api'] === $ctx['groups'];
                }),
            )
            ->willReturn('{"component":"Users/Index","props":{"errors":{}},"url":"/test","version":null}');

        $response = new InertiaResponse($this->twig, 'base.html.twig', $serializer);
        $request = Request::create('/test');

        $response->build(
            component: 'Users/Index',
            props: [],
            url: '/test',
            version: null,
            request: $request,
            serializationContext: ['groups' => ['api']],
        );
    }
}
