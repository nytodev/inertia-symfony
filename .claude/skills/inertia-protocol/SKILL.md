---
name: inertia-protocol
description: Complete reference for the Inertia.js v2 server-side protocol. Auto-loaded when working on InertiaResponse, InertiaListener, Inertia service, or any HTTP response handling.
---

# Inertia.js v2 Protocol Reference

## Request Detection

```php
// Detect an Inertia XHR request
$isInertia = $request->headers->has('X-Inertia')
    && 'true' === $request->headers->get('X-Inertia');

// Asset versioning
$clientVersion = $request->headers->get('X-Inertia-Version');

// Partial reload detection
$isPartial    = $request->headers->has('X-Inertia-Partial-Component');
$partialComp  = $request->headers->get('X-Inertia-Partial-Component'); // component name
$onlyProps    = $request->headers->get('X-Inertia-Partial-Data');       // CSV: include list
$exceptProps  = $request->headers->get('X-Inertia-Partial-Except');     // CSV: exclude list
$resetProps   = $request->headers->get('X-Inertia-Reset');              // CSV: reset before merge
$onceProps    = $request->headers->get('X-Inertia-Except-Once-Props');  // CSV: already-loaded once keys
$scrollIntent = $request->headers->get('X-Inertia-Infinite-Scroll-Merge-Intent'); // 'append'|'prepend'

// Other headers sent by client (no server action required unless noted):
// X-Requested-With: XMLHttpRequest
// Purpose: prefetch               (prefetch request — server may optimize, but not required)
// Cache-Control: no-cache         (reload requests)
// X-Inertia-Error-Bag: {bag}      (validation error bag name override)
// Precognition: true              (Precognition validation — out of scope for this bundle)
```

## Response Types

### HTML (first visit — no X-Inertia header)

```php
// v2: data-page attribute on the root div
$json = json_encode($pageObject, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$html = '<div id="app" data-page=\'' . $json . '\'></div>';
// Headers: Vary: X-Inertia

// NOTE: v3 uses a different approach:
// <script type="application/json" data-page="app">{...}</script><div id="app"></div>
// We are implementing v2 here.
```

### JSON (XHR visit — X-Inertia: true header present)

```php
$response = new JsonResponse($pageObject);
$response->headers->set('X-Inertia', 'true');
$response->headers->set('Vary', 'X-Inertia');
$response->headers->set('Content-Type', 'application/json');
```

## Page Object (v2) — Canonical Structure

```php
$pageObject = [
    // ALWAYS present in v2:
    'component'      => 'User/Edit',          // string
    'props'          => ['errors' => [], ...], // always has 'errors' key (default [])
    'url'            => '/user/123',           // RELATIVE path + query (no scheme/host)
    'version'        => '6b16b94d7c51',        // string|null
    'clearHistory'   => false,                 // always present in v2, even if false
    'encryptHistory' => false,                 // always present in v2, even if false
    // TODO: Inertia v3 — clearHistory/encryptHistory omitted when false

    // CONDITIONAL (omit key entirely when empty):
    'deferredProps'  => ['default' => ['comments'], 'sidebar' => ['related']],
    'mergeProps'     => ['posts'],             // or ['posts.data'] for sub-path
    'prependProps'   => ['notifications'],
    'deepMergeProps' => ['conversations'],
    'matchPropsOn'   => ['posts.id', 'notifications.id'],
    'onceProps'      => ['plans' => ['prop' => 'plans', 'expiresAt' => null]],
    'scrollProps'    => ['posts' => ['pageName' => 'page', 'nextPage' => 2,
                                     'previousPage' => null, 'currentPage' => 1]],
];
```

### Official page object examples (from inertiajs.com/docs/v2/core-concepts/the-protocol):

**Basic:**
```json
{ "component": "User/Edit", "props": { "errors": {}, "user": { "name": "Jonathan" } },
  "url": "/user/123", "version": "6b16b94d7c51cbe5b1fa42aac98241d5",
  "clearHistory": false, "encryptHistory": false }
```

**With deferred props:**
```json
{ "deferredProps": { "default": ["comments", "analytics"], "sidebar": ["relatedPosts"] } }
```

**With merge props:**
```json
{ "mergeProps": ["posts"], "prependProps": ["notifications"],
  "deepMergeProps": ["conversations"],
  "matchPropsOn": ["posts.id", "notifications.id", "conversations.data.id"] }
```

**With scroll props:**
```json
{ "mergeProps": ["posts.data"],
  "scrollProps": { "posts": { "pageName": "page", "previousPage": null, "nextPage": 2, "currentPage": 1 } } }
```

**With once props:**
```json
{ "onceProps": { "plans": { "prop": "plans", "expiresAt": null } } }
```

## Asset Versioning (409)

```php
// In kernel.request listener:
// IMPORTANT: version check must run BEFORE consuming any FlashBag keys (e.g. 'errors'),
// so that all flash data including 'errors' is still present when reflashing for the 409.
if (
    $request->isMethod('GET')
    && $isInertia
    && null !== $serverVersion
    && $clientVersion !== $serverVersion
) {
    // Reflash ALL flash data (consume + re-add) so nothing is lost across the hard reload.
    $session = $request->getSession();
    $flashBag = $session->getFlashBag();
    $flashes = $flashBag->all();  // all() consumes; peekAll() would cause duplication
    foreach ($flashes as $type => $messages) {
        foreach ($messages as $message) {
            $flashBag->add($type, $message);
        }
    }

    return new Response('', 409, [
        'X-Inertia-Location' => $request->getUri(), // absolute URL — browser navigates here
    ]);
}
// Only consume 'errors' flash AFTER the 409 path has been handled.
```

## 302 → 303 Redirect Conversion

Only on PUT, PATCH, DELETE — **not** POST. Spec: "When redirecting after a PUT, PATCH, or DELETE request, you must use a 303 response code."

```php
// In kernel.response listener:
if (302 === $response->getStatusCode()
    && \in_array($request->getMethod(), ['PUT', 'PATCH', 'DELETE'], true)) {
    $response->setStatusCode(303);
}
```

## Partial Reload Filtering Rules

```
isPartial = (X-Inertia-Partial-Data OR X-Inertia-Partial-Except) AND component matches

On full render (not partial):
  - errors: always resolved
  - LazyProp / optional(): SKIP
  - DeferProp: SKIP (goes to deferredProps metadata)
  - OnceProp: resolve UNLESS key in X-Inertia-Except-Once-Props
  - AlwaysProp: always resolve
  - MergeProp, ScrollProp, Closure, plain value: resolve

On partial reload:
  - errors: always included (ignores $only/$except)
  - AlwaysProp: always included (ignores $only/$except)
  - $except wins over $only when both present
  - LazyProp: only if key explicitly in $only
  - DeferProp: only if key explicitly in $only (deferred fetch)
  - OnceProp: X-Inertia-Except-Once-Props is IGNORED on partials (only full XHR visits)
  - OnceProp: follows normal $only/$except filtering
  - MergeProp, ScrollProp: follows $only/$except filtering
```

## HTTP Status Codes

| Code | When |
|------|------|
| 200  | Normal response (HTML or JSON) |
| 302  | Redirect (GET requests) |
| 303  | Redirect after PUT/PATCH/DELETE |
| 409  | Asset version mismatch (GET only) OR `Inertia::location()` external redirect |

## Props Types Summary

| Type | PHP API | Standard visit | Partial reload | Page object key |
|------|---------|----------------|----------------|-----------------|
| Direct value | `mixed` | ✅ Always | ✅ Optionally | `props` |
| Closure | `fn() => ...` | ✅ Always | ✅ Optionally | `props` |
| LazyProp | `optional()` *(our bundle: `lazy()`)* | ❌ Never | ✅ Only if in `$only` | `props` |
| AlwaysProp | `always()` | ✅ Always | ✅ Always | `props` |
| DeferProp | `defer($cb, $group)` | ❌ Never | ✅ Separate XHR | `deferredProps` |
| OnceProp | `once()` | ✅ First time | ❌ Skip if in `X-Inertia-Except-Once-Props` | `props` + `onceProps` |
| MergeProp | `merge()` / `deepMerge()` *(our bundle: `merge(deep:true)`)* | ✅ Yes | ✅ Yes | `props` + `mergeProps`/`prependProps`/`deepMergeProps` |
| ScrollProp | `scroll()` | ✅ Yes | ✅ Yes | `props` + `mergeProps` + `scrollProps` |

**Note on our bundle vs official v2 API:**
- Official: `Inertia::optional()` → our bundle: `$inertia->lazy()` (same class, different name)
- Official: `Inertia::deepMerge()` → our bundle: `$inertia->merge($cb, deep: true)` (missing factory)
- Official: `Inertia::defer()->once()` → our bundle: not yet supported
