# Contributing

Contributions are welcome. This bundle implements the [Inertia.js v2 server-side protocol](https://inertiajs.com/) — all changes must maintain full protocol compliance.

## Setup

```bash
git clone https://github.com/nytodev/inertia-bundle.git
cd inertia-bundle
composer install
```

## Branch strategy

| Type | Target branch |
|------|--------------|
| Bug fix | `2.x` (oldest maintained branch) |
| New feature | `2.x` |
| Breaking change | Not accepted on maintained branches |

Always create a topic branch from the correct base:

```bash
git checkout 2.x
git checkout -b fix/my-bug-fix
```

Rebase against the target branch before submitting:

```bash
git fetch origin
git rebase origin/2.x
```

## Running the checks

All checks must pass locally before pushing:

```bash
vendor/bin/phpunit                                 # test suite (95% coverage enforced in CI)
vendor/bin/phpstan analyse src/ tests/ --level=8   # static analysis
vendor/bin/php-cs-fixer fix --dry-run --diff       # code style check
vendor/bin/php-cs-fixer fix                        # auto-fix code style
```

## Submitting a pull request

1. Fork the repository and create a branch from `2.x`
2. Write tests first (TDD) — new code without tests will not be merged
3. Ensure all checks pass locally
4. Open a pull request against the `2.x` branch

**PR description must answer:**
- Is it a bug fix or a new feature?
- Does it break backward compatibility?
- Does it affect the Inertia.js v2 protocol output?

## Coding standards

Code style is enforced by **PHP CS Fixer** (`@PSR12` + `@Symfony` + `@Symfony:risky`):

- `declare(strict_types=1)` in every file
- `final` on every class
- Yoda conditions (`null === $value`)
- Strict comparisons only (`===`, `!==`)
- Short array syntax, ordered imports, no unused imports

## Protocol compliance

This bundle is a faithful implementation of the Inertia.js v2 protocol. Any change that affects HTTP responses (headers, page object structure, status codes) must:

- Conform to the [Inertia.js v2 protocol spec](https://inertiajs.com/)
- Be covered by a functional test in `tests/Functional/Protocol/`

## Backward compatibility

This bundle follows [Symfony's BC promise](https://symfony.com/doc/current/contributing/code/bc.html):

- No breaking changes in minor or patch releases
- Deprecations must be introduced before removal
- Document any BC break or deprecation in `CHANGELOG.md`

## Reporting bugs

Open a [GitHub issue](https://github.com/nytodev/inertia-bundle/issues) with:
- PHP version, Symfony version, bundle version
- Steps to reproduce
- Expected vs actual behavior

For security vulnerabilities, see [SECURITY.md](SECURITY.md).

## Code of Conduct

Please read and follow our [Code of Conduct](CODE_OF_CONDUCT.md).
