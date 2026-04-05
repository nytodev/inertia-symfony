CHANGELOG
=========

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
