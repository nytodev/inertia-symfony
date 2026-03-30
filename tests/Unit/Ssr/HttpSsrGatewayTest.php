<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Unit\Ssr;

use Nytodev\InertiaBundle\Ssr\HttpSsrGateway;
use Nytodev\InertiaBundle\Ssr\SsrResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class HttpSsrGatewayTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $page = ['component' => 'Home', 'props' => [], 'url' => '/'];

    public function testDispatchSuccessfulResponseReturnsSsrResponse(): void
    {
        $body = json_encode([
            'head' => ['<title>Home</title>', '<meta name="desc" content="hello">'],
            'body' => '<div id="app"><h1>Home</h1></div>',
        ]);
        $client = new MockHttpClient(new MockResponse((string) $body));
        $gateway = new HttpSsrGateway($client, 'http://127.0.0.1:13714');

        $result = $gateway->dispatch($this->page);

        self::assertInstanceOf(SsrResponse::class, $result);
        self::assertSame("<title>Home</title>\n<meta name=\"desc\" content=\"hello\">", $result->head);
        self::assertSame('<div id="app"><h1>Home</h1></div>', $result->body);
    }

    public function testDispatchHeadArrayJoinedWithNewline(): void
    {
        $body = json_encode([
            'head' => ['<title>A</title>', '<title>B</title>', '<title>C</title>'],
            'body' => '<div id="app"></div>',
        ]);
        $client = new MockHttpClient(new MockResponse((string) $body));
        $gateway = new HttpSsrGateway($client, 'http://127.0.0.1:13714');

        $result = $gateway->dispatch($this->page);

        self::assertNotNull($result);
        self::assertSame("<title>A</title>\n<title>B</title>\n<title>C</title>", $result->head);
    }

    public function testDispatchEmptyHeadReturnsEmptyString(): void
    {
        $body = json_encode(['head' => [], 'body' => '<div id="app"></div>']);
        $client = new MockHttpClient(new MockResponse((string) $body));
        $gateway = new HttpSsrGateway($client, 'http://127.0.0.1:13714');

        $result = $gateway->dispatch($this->page);

        self::assertNotNull($result);
        self::assertSame('', $result->head);
    }

    public function testDispatchHttpErrorReturnsNull(): void
    {
        $client = new MockHttpClient(new MockResponse('Server Error', ['http_code' => 500]));
        $gateway = new HttpSsrGateway($client, 'http://127.0.0.1:13714');

        $result = $gateway->dispatch($this->page);

        self::assertNull($result);
    }

    public function testDispatchNetworkFailureReturnsNull(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['error' => 'Connection refused']));
        $gateway = new HttpSsrGateway($client, 'http://127.0.0.1:13714');

        $result = $gateway->dispatch($this->page);

        self::assertNull($result);
    }

    public function testDispatchInvalidJsonReturnsNull(): void
    {
        $client = new MockHttpClient(new MockResponse('not-json'));
        $gateway = new HttpSsrGateway($client, 'http://127.0.0.1:13714');

        $result = $gateway->dispatch($this->page);

        self::assertNull($result);
    }

    public function testDispatchPostsToRenderEndpoint(): void
    {
        $capturedRequest = null;
        $callback = static function (string $method, string $url, array $options) use (&$capturedRequest): MockResponse {
            $capturedRequest = ['method' => $method, 'url' => $url, 'body' => $options['body'] ?? ''];

            return new MockResponse((string) json_encode([
                'head' => [],
                'body' => '<div id="app"></div>',
            ]));
        };

        $client = new MockHttpClient($callback);
        $gateway = new HttpSsrGateway($client, 'http://127.0.0.1:13714');
        $gateway->dispatch($this->page);

        self::assertNotNull($capturedRequest);
        self::assertSame('POST', $capturedRequest['method']);
        self::assertStringEndsWith('/render', $capturedRequest['url']);
        self::assertStringContainsString('"component"', $capturedRequest['body']);
    }

    public function testDispatchTrailingSlashInUrlNormalizesEndpoint(): void
    {
        $capturedUrl = null;
        $callback = static function (string $method, string $url) use (&$capturedUrl): MockResponse {
            $capturedUrl = $url;

            return new MockResponse((string) json_encode(['head' => [], 'body' => '<div id="app"></div>']));
        };

        $client = new MockHttpClient($callback);
        $gateway = new HttpSsrGateway($client, 'http://127.0.0.1:13714/');
        $gateway->dispatch($this->page);

        self::assertSame('http://127.0.0.1:13714/render', $capturedUrl);
    }
}
