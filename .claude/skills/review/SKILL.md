---
name: review
description: Run a complete code review of the Inertia bundle. Checks protocol compliance and Symfony bundle conventions via static analysis (no automated tools).
allowed-tools: Read, Grep, Glob
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

## Known acceptable exceptions (do NOT flag these as violations)

- `dump()` and `dd()` in `src/Testing/AssertableInertiaPage.php` — intentional testing DSL methods,
  same as Laravel's `Debugging` trait. Acceptable in `src/Testing/`.
- `Assert::assertTrue(true)` in `assertPropMissing()` — intentional, prevents PHPUnit "risky test"
  (no assertions) warning on the "path not found = key is missing" early-return branch.
- `static` return type on `final` class factory methods — equivalent to `self`, PHPStan does not flag it.
- `X-Inertia-Location` header value is the **absolute** URL (`$request->getUri()`) — this is correct per
  protocol and matches Laravel's `$request->fullUrl()`. It is NOT a page object `url` field (which must be relative).
- `Vary: X-Inertia` on first-visit HTML responses (`InertiaResponse.php:142`) — intentional cache protection.
  Laravel sets it unconditionally on ALL responses (Middleware.php:139). Without it, a CDN/proxy could serve
  a cached JSON response to a real browser (or vice-versa). Do NOT remove.
- `X-Inertia` guard on 302→303 conversion (`InertiaListener.php:99`) — intentional divergence from Laravel.
  Laravel uses opt-in middleware (only Inertia routes); Symfony listener fires globally on ALL requests.
  Removing the guard would convert 302→303 on non-Inertia API routes too. The guard is correct in Symfony's
  architecture. Native browser PUT/PATCH/DELETE forms don't exist in HTML, so the "missed conversion" risk is nil.

## Critical protocol invariant to check

In `InertiaListener::onKernelRequest()`, the **version mismatch 409 check must run BEFORE any
`FlashBag::get()` call**. `get()` consumes flash entries; if 'errors' is consumed before the
reflash block, it will be silently lost on the 409 hard-reload cycle.

## Report Format
Summarize findings as:
- ✅ Protocol compliance: X/Y checks passed
- ✅ Bundle conventions: X/Y checks passed

List all issues with file:line references and suggested fixes.