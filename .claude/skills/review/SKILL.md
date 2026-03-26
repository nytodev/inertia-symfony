---
name: review
description: Run a complete code review of the Inertia bundle. Checks protocol compliance, Symfony bundle conventions, PHP quality, and tests.
allowed-tools: Bash(vendor/bin/phpunit:*), Bash(vendor/bin/phpstan:*), Bash(vendor/bin/php-cs-fixer:*), Read, Bash(find:*)
---

# Bundle Review — symfony-inertia-bundle

Run this review in parallel using sub-agents:

## 1. Protocol Compliance Review
Use agent `protocol-validator` on:
- `src/Response/InertiaResponse.php`
- `src/EventListener/InertiaListener.php`
- `src/Service/Inertia.php`

## 2. Bundle Convention Review
Use agent `bundle-reviewer` on:
- `src/InertiaBundle.php`
- `config/definition.php`
- `config/services.yaml`
- `composer.json`

## 3. Automated checks
```bash
# Tests
vendor/bin/phpunit --testdox

# PHPStan
vendor/bin/phpstan analyse src/ tests/ --level=8 --no-progress

# CS Fixer
vendor/bin/php-cs-fixer fix --dry-run --diff src/ tests/
```

## 4. Coverage check
```bash
vendor/bin/phpunit --coverage-text | grep "Lines:" | head -5
```
Minimum: 95%

## Report Format
Summarize findings as:
- ✅ Protocol compliance: X/Y checks passed
- ✅ Bundle conventions: X/Y checks passed  
- ✅ Tests: X passed, 0 failed
- ✅ PHPStan: 0 errors
- ✅ CS Fixer: clean
- ✅ Coverage: XX%

List all issues with file:line references and suggested fixes.