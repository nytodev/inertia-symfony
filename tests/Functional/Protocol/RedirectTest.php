<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class RedirectTest extends FunctionalTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();
        // Disable following redirects so we can assert on the redirect response itself.
        $this->client->followRedirects(false);
    }

    public function testRedirectAfter302OnGetRemains302(): void
    {
        // GET requests from Inertia that produce a 302 should stay 302.
        // This tests a non-Inertia GET redirect to confirm no mutation.
        $this->client->request('GET', '/test');
        self::assertResponseIsSuccessful();
    }

    public function testRedirectAfter302OnPutConvertedTo303(): void
    {
        $this->client->request('PUT', '/test/redirect', [], [], ['HTTP_X_INERTIA' => 'true']);
        self::assertResponseStatusCodeSame(303);
    }

    public function testRedirectAfter302OnPatchConvertedTo303(): void
    {
        $this->client->request('PATCH', '/test/redirect', [], [], ['HTTP_X_INERTIA' => 'true']);
        self::assertResponseStatusCodeSame(303);
    }

    public function testRedirectAfter302OnDeleteConvertedTo303(): void
    {
        $this->client->request('DELETE', '/test/redirect', [], [], ['HTTP_X_INERTIA' => 'true']);
        self::assertResponseStatusCodeSame(303);
    }

    public function testRedirectAfter302OnPostRemains302(): void
    {
        // POST is not in PUT/PATCH/DELETE — 302 must NOT be converted to 303.
        $this->client->request('POST', '/test/redirect', [], [], ['HTTP_X_INERTIA' => 'true']);
        self::assertResponseStatusCodeSame(302);
    }
}
