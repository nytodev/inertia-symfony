# Inertia.js Symfony Bundle

Symfony bundle implementing the [Inertia.js](https://inertiajs.com/) v2 server-side protocol — the Symfony equivalent of [`inertiajs/inertia-laravel`](https://github.com/inertiajs/inertia-laravel).

[![Tests](https://github.com/nytodev/inertia-bundle/actions/workflows/tests.yml/badge.svg)](https://github.com/nytodev/inertia-bundle/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/nytodev/inertia-bundle.svg?style=flat-square)](https://packagist.org/packages/nytodev/inertia-bundle)
[![PHP](https://img.shields.io/packagist/php-v/nytodev/inertia-bundle.svg?style=flat-square)](https://packagist.org/packages/nytodev/inertia-bundle)
[![License](https://img.shields.io/packagist/l/nytodev/inertia-bundle.svg?style=flat-square)](LICENSE)

---

## Requirements

PHP ^8.1 · Symfony ^6.4 / ^7.4 / ^8.0 · Twig ^3.0

## Installation

```bash
composer require nytodev/inertia-bundle
```

Without Symfony Flex, register the bundle manually in `config/bundles.php`:

```php
Nytodev\InertiaBundle\InertiaBundle::class => ['all' => true],
```

## Configuration

```yaml
# config/packages/inertia.yaml
inertia:
    root_view: base.html.twig   # Root Twig template (default: base.html.twig)
    version: null               # Asset version string — triggers full reload on change
    encrypt_history: false      # Globally encrypt browser history state
    ssr_enabled: false          # Enable SSR (requires symfony/http-client)
    ssr_url: 'http://127.0.0.1:13714'
    ssr_bundle: null            # Path to SSR JS bundle, auto-detected if null
```

## Basic usage

```php
use Nytodev\InertiaBundle\Service\Inertia;

#[Route('/users')]
public function index(Inertia $inertia): Response
{
    return $inertia->render('Users/Index', [
        'users' => $this->repository->findAll(),
    ]);
}
```

Your root Twig template:

```twig
<!DOCTYPE html>
<html>
<head>
    {{ inertiaHead(page) }}
</head>
<body>
    {{ inertia(page) }}
</body>
</html>
```

## Documentation

Full documentation is available in [`docs/`](docs/).

## License

MIT — see [LICENSE](LICENSE).
