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

// Other standard headers sent by the client (no server action required):
// X-Requested-With: XMLHttpRequest
// Accept: text/html, application/xhtml+xml
// Purpose: prefetch               (for prefetch requests)
// Cache-Control: no-cache         (for reload requests)
// X-Inertia-Error-Bag: {bag}      (validation error bag name)
// X-Inertia-Infinite-Scroll-Merge-Intent: append|prepend
// Precognition: true              (Precognition validation, out of scope)
```

## Response Types

### HTML (first visit)

```php
// v2: data-page attribute on the root div
$json = json_encode($pageObject, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
$html = '<div id="app" data-page=\'' . $json . '\'></div>';

// NOTE: v3 uses a different approach:
// <script type="application/json" data-page="app">{...}</script><div id="app"></div>
// We are implementing v2 here.
```

### JSON (XHR visit)

```php
$response = new JsonResponse($pageObject);
$response->headers->set('X-Inertia', 'true');
$response->headers->set('Vary', 'X-Inertia');
```

## Page Object (v2)

$pageObject = [
    // Always present in v2:
    'component'      => 'User/Edit',          // string
    'props'          => ['errors' => [], ...], // always has 'errors' key
    'url'            => '/user/123',           // RELATIVE path + query (no scheme/host)
    'version'        => '6b16b94d7c51',        // string|null
    'clearHistory'   => false,                 // always present in v2, even if false
    'encryptHistory' => false,                 // always present in v2, even if false
    // Conditional (omit if empty):
    'deferredProps'  => ['default' => ['comments'], 'sidebar' => ['related']],
    'mergeProps'     => ['posts'],
    'prependProps'   => ['notifications'],
    'deepMergeProps' => ['conversations'],
    'matchPropsOn'   => ['posts.id', 'notifications.id'],
    'onceProps'      => ['plans' => ['prop' => 'plans', 'expiresAt' => null]],
    'scrollProps'    => ['posts' => ['pageName' => 'page', 'nextPage' => 2]],
];
```

## Asset Versioning (409)

```php
// In kernel.request listener:
if (
    $request->isMethod('GET')
    && $isInertia
    && null !== $serverVersion
    && $clientVersion !== $serverVersion
) {
    // Reflash flash data before 409
    $session = $request->getSession();
    $flashBag = $session->getFlashBag();
    foreach ($flashBag->peekAll() as $type => $messages) {
        foreach ($messages as $message) {
            $flashBag->add($type, $message);
        }
    }

    return new Response('', 409, [
        'X-Inertia-Location' => $request->getUri(),
    ]);
}
```

## 302 → 303 Redirect Conversion

Only on PUT, PATCH, DELETE — **not** POST. Official spec: "When redirecting after a PUT, PATCH, or DELETE request, you must use a 303 response code."

```php
// In kernel.response listener:
$method = $request->getMethod();
$status = $response->getStatusCode();

if (302 === $status && \in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
    $response->setStatusCode(303);
}
```

## Partial Reload Props Filtering

```php
private function resolveProps(array $allProps, Request $request): array
{
    if (!$request->headers->has('X-Inertia-Partial-Component')) {
        // Full render: resolve non-deferred, non-once (if already loaded) props
        return $this->resolvePropValues($allProps, excludeDeferred: true);
    }

    $only   = $this->parseHeaderCsv($request->headers->get('X-Inertia-Partial-Data', ''));
    $except = $this->parseHeaderCsv($request->headers->get('X-Inertia-Partial-Except', ''));

    $filtered = [];
    foreach ($allProps as $key => $value) {
        // errors is ALWAYS included
        if ('errors' === $key) {
            $filtered[$key] = $value;
            continue;
        }

        if ($except !== []) {
            // Except mode: include everything not in the except list
            if (!in_array($key, $except, true)) {
                $filtered[$key] = $value;
            }
        } elseif ($only !== []) {
            // Only mode: include only explicitly requested props
            if (in_array($key, $only, true)) {
                $filtered[$key] = $value;
            }
        } else {
            $filtered[$key] = $value;
        }
    }

    return $this->resolvePropValues($filtered, excludeDeferred: false);
}

private function parseHeaderCsv(string $header): array
{
    if ('' === $header) {
        return [];
    }
    return array_filter(array_map('trim', explode(',', $header)));
}
```

## Props Types Summary

| Type | Class | Standard visit | Partial reload | Page object key |
|------|-------|---------------|----------------|-----------------|
| Direct value | `mixed` | ✅ Always | ✅ Optionally | `props` |
| Closure | `Closure` | ✅ Always | ✅ Optionally | `props` |
| LazyProp (`optional()`) | `LazyProp` | ❌ Never | ✅ Only if in `$only` list | `props` |
| AlwaysProp (`always()`) | *(not yet impl.)* | ✅ Always | ✅ Always | `props` |
| DeferProp (`defer()`) | `DeferProp` | ❌ Never | ✅ Separate XHR per group | `deferredProps` metadata |
| OnceProp (`once()`) | `OnceProp` | ✅ First time | ❌ Skip if in `X-Inertia-Except-Once-Props` | `onceProps` metadata |
| MergeProp (`merge()`) | `MergeProp` | ✅ Yes | ✅ Yes | `props` + `mergeProps`/`prependProps`/`deepMergeProps` |

**OnceProp features:** `.as('key')` — share across pages with different prop names; `.until($date)` — expiry; `.fresh()` — force re-resolve

## HTTP Status Codes Summary

| Code | When |
|------|------|
| 200 | Normal response (HTML or JSON) |
| 302 | Redirect (converted to 303 after non-GET Inertia requests) |
| 303 | Redirect after PUT/PATCH/DELETE (prevents duplicate form submission) |
| 409 | Asset version mismatch (GET only) or external redirect |