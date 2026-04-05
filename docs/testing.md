# Testing

The bundle ships with `AssertableInertiaPage`, a fluent assertion helper for testing Inertia responses.

## Setup

Extend `FunctionalTestCase` in your functional tests:

```php
use Nytodev\InertiaBundle\Tests\Functional\FunctionalTestCase;

class UserControllerTest extends FunctionalTestCase
{
    // ...
}
```

`FunctionalTestCase` provides `assertInertia(Response $response, callable $callback)`.

## assertInertia()

Pass a callback that receives an `AssertableInertiaPage` instance:

```php
public function testUsersIndexRendersCorrectly(): void
{
    $client = static::createClient();
    $client->request('GET', '/users', [], [], ['HTTP_X_INERTIA' => 'true']);

    $this->assertInertia($client->getResponse(), function ($page) {
        $page->component('Users/Index')
             ->has('users')
             ->count('users', 3)
             ->where('users.0.name', 'Alice');
    });
}
```

`assertInertia()` works with both XHR responses (`X-Inertia: true`) and plain HTML first-visit responses (it parses `data-page` from the HTML in that case).

## Available assertions

| Method | Description |
|--------|-------------|
| `component(string $name)` | Assert the rendered component name |
| `url(string $url)` | Assert the page URL |
| `version(string $version)` | Assert the asset version |
| `has(string $key)` | Assert a prop key exists (dot notation supported) |
| `missing(string $key)` | Assert a prop key does not exist |
| `where(string $key, mixed $expected)` | Assert a prop value — pass a Closure for custom assertions |
| `whereAll(array $props)` | Assert multiple prop values at once |
| `count(string $key, int $expected)` | Assert the count of a prop array |
| `dump()` | Dump the page object and continue chaining |
| `dd()` | Dump the page object and stop execution |

All assertion methods return `$this` for chaining.

## Dot notation

`has()`, `missing()`, `where()`, and `count()` support dot notation to traverse nested props:

```php
$page->has('user.address.city')
     ->where('user.address.city', 'Paris')
     ->missing('user.password');
```

## Closure assertions

Pass a Closure to `where()` for custom logic:

```php
$page->where('users', function (array $users): bool {
    return count($users) > 0 && $users[0]['active'] === true;
});
```

## Testing without FunctionalTestCase

Use `AssertableInertiaPage::fromResponse()` directly:

```php
use Nytodev\InertiaBundle\Testing\AssertableInertiaPage;

$page = AssertableInertiaPage::fromResponse($response);
$page->component('Users/Index')->has('users');
```
