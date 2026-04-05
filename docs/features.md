# Asset versioning, redirects & history

## Asset versioning

When the server asset version differs from the version the client loaded, Inertia triggers a full page reload so the user picks up new assets.

The `version` config option accepts any **string**. The recommended approach is an environment variable set at deploy time.

```yaml
# config/packages/inertia.yaml
inertia:
    version: '%env(ASSET_VERSION)%'
```

### With Webpack Encore

Enable versioning in `webpack.config.js`:

```js
// webpack.config.js
Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    // ...
    .enableVersioning()
```

Encore generates `public/build/manifest.json` with content-hashed filenames. Use its hash as the Inertia version at deploy time:

```bash
# .env or deploy script
ASSET_VERSION=$(md5sum public/build/manifest.json | cut -d' ' -f1)
```

```yaml
# config/packages/assets.yaml — lets Twig resolve versioned filenames
framework:
    assets:
        json_manifest_path: '%kernel.project_dir%/public/build/manifest.json'
```

### With vite-plugin-symfony

Vite also generates a `public/build/manifest.json`. Same approach:

```bash
ASSET_VERSION=$(md5sum public/build/manifest.json | cut -d' ' -f1)
```

When the version changes, the bundle returns `409 Conflict` + `X-Inertia-Location` on `GET` requests. The Inertia client performs a hard browser redirect to pick up new assets.

> Version checking only applies to `GET` requests. `POST`/`PUT`/etc. are not checked.

---

## Redirects

### Automatic 302 → 303 conversion

After a `PUT`, `PATCH`, or `DELETE` request, browsers will re-submit the same method on a `302` redirect. The Inertia protocol requires `303 See Other` in these cases to force a `GET`. The bundle handles this automatically — no action needed.

### External redirects

Force a full browser navigation outside the SPA (OAuth flows, external URLs):

```php
return $inertia->location('https://auth.example.com/oauth/redirect');
```

- **Inertia XHR request** → returns `409 Conflict` + `X-Inertia-Location` header.
- **First visit (non-XHR)** → returns a standard `302` redirect.

---

## History

### Clear history

Clears the browser's history stack on the next render. Use after logout or when the user should not be able to navigate back.

```php
$inertia->clearHistory();
return $inertia->render('Auth/Login');
```

### Encrypt history

Encrypts the browser history state so sensitive page data is not stored in plain text in `window.history.state`.

**Per-render:**

```php
$inertia->encryptHistory();
return $inertia->render('Account/BillingDetails');
```

**Globally (all pages):**

```yaml
inertia:
    encrypt_history: true
```

When enabled globally, individual renders still start with encryption on. Call `encryptHistory()` per-render is a no-op when the global default is already `true`.
