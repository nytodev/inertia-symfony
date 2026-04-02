<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Tests\Functional\Protocol;

use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;

final class MergePropsPathTest extends FunctionalTestCase
{
    // --- MergeProp appendsAtPaths ---

    public function testMergeAtPathXhrFullRenderEmitsDotNotationInMergeProps(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/merge-at-path', [], [], ['HTTP_X_INERTIA' => 'true']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertArrayHasKey('mergeProps', $data);
        self::assertContains('posts.data', $data['mergeProps']);
    }

    public function testMergeAtPathXhrFullRenderDoesNotEmitRootLevelEntry(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/merge-at-path', [], [], ['HTTP_X_INERTIA' => 'true']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertArrayHasKey('mergeProps', $data);
        self::assertNotContains('posts', $data['mergeProps']);
    }

    // --- MergeProp prependsAtPaths ---

    public function testPrependAtPathXhrFullRenderEmitsDotNotationInPrependProps(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/prepend-at-path', [], [], ['HTTP_X_INERTIA' => 'true']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertArrayHasKey('prependProps', $data);
        self::assertContains('posts.items', $data['prependProps']);
    }

    public function testPrependAtPathXhrFullRenderDoesNotEmitRootLevelEntry(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/prepend-at-path', [], [], ['HTTP_X_INERTIA' => 'true']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertArrayNotHasKey('mergeProps', $data);
        if (\array_key_exists('prependProps', $data)) {
            self::assertNotContains('posts', $data['prependProps']);
        }
    }

    // --- Mixed root + path ---

    public function testMixedRootAndPathXhrFullRenderBothCoexistCorrectly(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/merge-path-mixed', [], [], ['HTTP_X_INERTIA' => 'true']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertArrayHasKey('mergeProps', $data);
        // root-level prop
        self::assertContains('posts', $data['mergeProps']);
        // path-specific prop
        self::assertContains('comments.data', $data['mergeProps']);
        // no spurious root entry for comments
        self::assertNotContains('comments', $data['mergeProps']);
    }

    // --- DeferProp appendAt ---

    public function testDeferAtPathXhrFullRenderEmitsDotNotationInMergeProps(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/defer-at-path', [], [], ['HTTP_X_INERTIA' => 'true']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertArrayHasKey('mergeProps', $data);
        self::assertContains('deferred.data', $data['mergeProps']);
    }

    public function testDeferAtPathXhrFullRenderDoesNotEmitRootLevelEntry(): void
    {
        $client = self::createClient();
        $client->request('GET', '/test/defer-at-path', [], [], ['HTTP_X_INERTIA' => 'true']);

        self::assertResponseIsSuccessful();
        $data = json_decode((string) $client->getResponse()->getContent(), true);

        self::assertArrayHasKey('mergeProps', $data);
        self::assertNotContains('deferred', $data['mergeProps']);
    }
}
