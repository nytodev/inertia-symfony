CHANGELOG
=========

3.0.0
-----

 * Added full Inertia.js v3 server-side protocol compliance
 * Changed HTML embedding: `<script data-page="app" type="application/json">` replaces `data-page` attribute on `<div>`
 * Changed `clearHistory` and `encryptHistory`: omitted from page object when `false` (v2 always included them)
 * Changed `flash`: moved to top-level page object key, no longer nested inside `props`
 * Added `sharedProps` top-level field listing keys injected via `share()` (opt-out via `expose_shared_prop_keys: false`)
 * Added `preserveFragment` flag — signals client to preserve URL fragment after redirect
 * Added `X-Inertia-Redirect` header emitted on fragment redirects
 * Added `ExceptionResponse` DTO and `InertiaExceptionListener` for Inertia-aware error handling
 * Added `Inertia::handleExceptionsUsing()` to replace error pages with Inertia components
 * Added `BundleDetector` — auto-detects SSR bundle (`ssr.mjs` / `ssr.js`) without hardcoded path
 * Added `SsrState` — centralises per-request SSR render cache
 * Improved `HttpSsrGateway` — HTTP 4xx/5xx error handling, `throwOnError` support, path exclusion
 * Changed `Inertia::render()` now accepts a `BackedEnum` as component name
 * Added `AssertableInertiaPage::configure()` — define component paths and extensions to scan
 * Added component file existence assertion in `AssertableInertiaPage::component()`
 * Renamed `LazyProp` to `OptionalProp` — `lazy()` alias kept for backwards compatibility
 * Added `expose_shared_prop_keys` configuration option
 * Added optional Symfony Normalizer support — pass a `$serializationContext` array to `Inertia::render()` or `renderInertia()` to normalize PHP objects (e.g. Doctrine entities) in props via `NormalizerInterface`; requires `symfony/serializer`

2.0.2
-----

 * Fixed missing `symfony/twig-bundle` dependency declaration in `composer.json`

2.0.1
-----

 * Added Dependabot for weekly Composer and GitHub Actions updates
 * Added `.gitattributes` to exclude dev files from Packagist archives
 * Added `CONTRIBUTING.md` and `CODE_OF_CONDUCT.md`
 * Updated `SECURITY.md`
 * Added Packagist keywords and homepage to `composer.json`
 * Bumped `phpstan/phpstan` to `^2.1`

2.0.0
-----

 * Added full Inertia.js v2 server-side protocol compliance (page object, XHR detection, asset versioning 409, 302→303 redirect conversion)
 * Added partial reloads via `X-Inertia-Partial-Data`, `X-Inertia-Partial-Except` and `X-Inertia-Reset` headers
 * Added `clearHistory` and `encryptHistory` flags on the page object
 * Added `matchPropsOn` deduplication metadata for merge props
 * Added `Inertia::location()` for external redirects
 * Added `LazyProp` (`lazy()` / `optional()`) — resolved only on explicit partial reload request
 * Added `AlwaysProp` (`always()`) — always included, even in partial reloads
 * Added `DeferProp` (`defer()`) — fetched in a separate XHR by the client
 * Added `OnceProp` (`once()`) — sent once and cached by the client, with `.as()`, `.until()`, `.fresh()` modifiers
 * Added `MergeProp` (`merge()`) — client-side merge with `.prepend()` and `.deepMerge()` variants
 * Added `ScrollProp` (`scroll()`) — infinite-scroll pagination prop with defer support
 * Added `once()` flag on `DeferProp` and `LazyProp`
 * Added `deepMerge()` shorthand on the `Inertia` service
 * Added `Inertia::errors()` for validation errors auto-injection
 * Added `Inertia::clearHistory()` and `encryptHistory()` history control methods
 * Added flash data automatically merged into page props
 * Added server-side rendering (SSR) via external Node.js SSR server
 * Added `inertia:start-ssr`, `inertia:stop-ssr` and `inertia:check-ssr` console commands
 * Added `AssertableInertiaPage` fluent testing DSL
 * Added `ssr_enabled`, `ssr_url` and `encrypt_history` configuration options
