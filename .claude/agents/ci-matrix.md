---
name: ci-matrix
description: Generates and validates GitHub Actions CI matrix for PHP × Symfony compatibility. Use when creating or updating .github/workflows/tests.yml.
---

# CI Matrix Agent — symfony-inertia-bundle

You manage the GitHub Actions CI configuration for maximum compatibility coverage.

## Target Matrix

| PHP | Symfony | Composer flags | SYMFONY_DEPRECATIONS_HELPER |
|-----|---------|----------------|------------------------------|
| 8.1 | 6.4.*   | --prefer-lowest | disabled=1 |
| 8.2 | 6.4.*   | (none)          | max[direct]=0 |
| 8.3 | 6.4.*   | (none)          | max[direct]=0 |
| 8.2 | 7.4.*   | (none)          | max[direct]=0 |
| 8.3 | 7.4.*   | (none)          | max[direct]=0 |
| 8.4 | 7.4.*   | (none)          | max[direct]=0 |
| 8.4 | 8.0.*   | (none)          | max[direct]=0 |

## Canonical `tests.yml`

```yaml
name: Tests

on:
  push:
    branches: [ main ]
  pull_request:
    branches: [ main ]

jobs:
  tests:
    name: PHP ${{ matrix.php }} / Symfony ${{ matrix.symfony }}
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        include:
          - php: '8.1'
            symfony: '6.4.*'
            composer-flags: '--prefer-lowest'
            deprecations: 'disabled=1'
          - php: '8.2'
            symfony: '6.4.*'
            deprecations: 'max[direct]=0'
          - php: '8.3'
            symfony: '6.4.*'
            deprecations: 'max[direct]=0'
          - php: '8.2'
            symfony: '7.4.*'
            deprecations: 'max[direct]=0'
          - php: '8.3'
            symfony: '7.4.*'
            deprecations: 'max[direct]=0'
          - php: '8.4'
            symfony: '7.4.*'
            deprecations: 'max[direct]=0'
          - php: '8.4'
            symfony: '8.0.*'
            deprecations: 'max[direct]=0'

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: json, mbstring
          coverage: none

      - name: Install Symfony Flex globally
        run: |
          composer global config --no-plugins allow-plugins.symfony/flex true
          composer global require --no-progress --no-scripts --no-plugins symfony/flex

      - name: Install dependencies
        env:
          SYMFONY_REQUIRE: ${{ matrix.symfony }}
        run: composer update ${{ matrix.composer-flags }} --prefer-dist --no-progress --no-interaction

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse src/ --level=8 --no-progress

      - name: Run PHP CS Fixer
        run: vendor/bin/php-cs-fixer fix --dry-run --diff --no-interaction

      - name: Run tests
        env:
          SYMFONY_DEPRECATIONS_HELPER: ${{ matrix.deprecations }}
        run: vendor/bin/phpunit --no-coverage

  coverage:
    name: Coverage (PHP 8.3 / Symfony 7.4)
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: xdebug

      - name: Install Symfony Flex globally
        run: |
          composer global config --no-plugins allow-plugins.symfony/flex true
          composer global require --no-progress symfony/flex

      - name: Install dependencies
        env:
          SYMFONY_REQUIRE: '7.4.*'
        run: composer update --prefer-dist --no-progress

      - name: Run tests with coverage
        env:
          SYMFONY_DEPRECATIONS_HELPER: 'max[direct]=0'
        run: vendor/bin/phpunit --coverage-text --coverage-clover=coverage.xml

      - name: Check minimum coverage (95%)
        run: |
          COVERAGE=$(grep -oP 'Lines:\s+\K[\d.]+(?=%)' coverage.xml | head -1)
          echo "Coverage: ${COVERAGE}%"
          if (( $(echo "$COVERAGE < 95" | bc -l) )); then
            echo "Coverage ${COVERAGE}% is below 95% minimum"
            exit 1
          fi
```

## Validation Checklist

- [ ] All supported PHP versions are tested (8.1, 8.2, 8.3, 8.4)
- [ ] All supported Symfony LTS versions are tested (6.4, 7.4)
- [ ] Symfony 8.0 tested with PHP 8.4
- [ ] Lowest dependency test with `--prefer-lowest` on PHP 8.1 + Symfony 6.4
- [ ] `SYMFONY_DEPRECATIONS_HELPER=max[direct]=0` on all non-lowest tests
- [ ] `SYMFONY_DEPRECATIONS_HELPER=disabled=1` on lowest test
- [ ] `fail-fast: false` to see all failures, not just the first
- [ ] Coverage job separate (not slowing down the main matrix)
- [ ] Coverage minimum threshold enforced (95%)
- [ ] PHPStan runs in CI
- [ ] PHP CS Fixer runs in CI (dry-run mode)
