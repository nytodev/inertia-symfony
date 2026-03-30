# CLAUDE.md — symfony-inertia-bundle

Symfony Bundle implementing the **Inertia.js v2 server-side protocol** — the equivalent of
`inertiajs/inertia-laravel` for Symfony 6.4 / 7.4 / 8.0.

---

## Project overview

| Key | Value |
|-----|-------|
| Type | Reusable Symfony Bundle (not an app) |
| Inertia.js | v2 protocol (v3 migration path planned) |
| Symfony min | 6.4 LTS |
| PHP min | 8.1 |
| Bundle class | `AbstractBundle` — never `Bundle` + separate `Extension` |
| Packagist name | `nytodev/inertia-bundle` |
| DI alias | `inertia` |
| Main service | `Nytodev\InertiaBundle\Service\Inertia` |

---

## Verification commands — run after every change

```bash
vendor/bin/phpunit                                    # full test suite
vendor/bin/phpunit tests/Unit/                        # unit tests only
vendor/bin/phpunit tests/Functional/                  # functional tests only
vendor/bin/phpstan analyse src/ tests/ --level=8      # static analysis
vendor/bin/php-cs-fixer fix --dry-run --diff          # code style check
vendor/bin/php-cs-fixer fix                           # code style fix
```

---

## PHP conventions

- `declare(strict_types=1)` in every PHP file
- `final` on every class unless extension is explicitly supported
- `readonly` on every property that doesn't mutate
- Constructor promotion for all DI: `public function __construct(private readonly Foo $foo)`
- Strict comparisons only: `===` and `!==`, never `==`
- Yoda conditions: `if (null === $value)` not `if ($value === null)`
- `match` over `switch` for expressions
- camelCase for variables/methods, PascalCase for classes
- Max 800 lines per file (200–400 is typical)
- No `var_dump`, `dd`, `dump` in production code

---

## Symfony Bundle conventions

### What to do
- Use `AbstractBundle` — `configure()` for schema, `loadExtension()` for services
- Import config schema from `config/definition.php` via `$definition->import('../config/definition.php')`
- Load services via `$container->import('../config/services.yaml')` (physical path, not `@Bundle`)
- Prefix all services with `inertia.*`
- Declare all services `public: false`
- Create public aliases for autowiring: `Nytodev\InertiaBundle\Service\Inertia: { alias: inertia.service, public: true }`
- Name event listeners with suffix `Listener` (not `Subscriber`)
- `$config` in `loadExtension()` is already flat and validated — use it directly

### What NOT to do
- Never create `src/DependencyInjection/InertiaExtension.php`
- Never use `autowire: true` or `autoconfigure: true` in `config/services.yaml`
- Never use logical paths `@InertiaBundle/config/...`
- Never call `processConfiguration()` in `loadExtension()` — AbstractBundle does it already
- Never hardcode config values in PHP classes — always inject via DI

---

## Inertia.js v2 protocol — critical rules

### First visit (no `X-Inertia` header)
Returns full HTML with `data-page` attribute on root div:
```html
<div id="app" data-page='{"component":"...","props":{},...}'></div>
```
JSON must be escaped: `JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT`

### XHR visit (`X-Inertia: true` header present)
Returns JSON page object with headers:
- `Content-Type: application/json`
- `X-Inertia: true`
- `Vary: X-Inertia`

### Page object fields (v2)

**Always present:**
```json
{
    "component": "string",
    "props": { "errors": {} },
    "url": "/path?query",
    "version": "string|null",
    "clearHistory": false,
    "encryptHistory": false
}
```
- `url` is a **relative path + query string** (e.g. `/users?page=2`), never an absolute URL with scheme/host
- `clearHistory` and `encryptHistory` are **always present** in v2, even if `false` — this changes in v3

**Conditionally present (omitted if empty):**
```json
{
    "deferredProps":  { "default": ["comments"], "sidebar": ["related"] },
    "mergeProps":     ["posts"],
    "prependProps":   ["notifications"],
    "deepMergeProps": ["conversations"],
    "matchPropsOn":   ["posts.id"],
    "onceProps":      { "plans": { "prop": "plans", "expiresAt": null } },
    "scrollProps":    { "posts": { "pageName": "page", "nextPage": 2 } }
}
```

### Asset versioning
- `X-Inertia-Version` header differs from server version → `409 Conflict` + `X-Inertia-Location` header
- Only on `GET` requests
- Reflash session flash data before returning 409

### Redirects
- `302` after `PUT`/`PATCH`/`DELETE` → convert to `303 See Other`
- `302` after `GET` → keep as `302`

### Partial reloads
- `X-Inertia-Partial-Data` → include only listed props (CSV)
- `X-Inertia-Partial-Except` → exclude listed props (CSV); if both headers present, `Except` wins
- `X-Inertia-Reset` → reset (clear) listed props before merging (used with merge props)
- `errors` prop is **always** included regardless of partial reload filters
- `LazyProp` (optional) closures are **only** resolved when the key is explicitly in `X-Inertia-Partial-Data` — they are NOT resolved in except-only partial reloads or full renders

### Props types and evaluation

| Type | Standard visit | Partial reload | Notes |
|------|---------------|----------------|-------|
| `mixed` (direct value) | ✅ Always | ✅ Optionally | Resolved always |
| `Closure` | ✅ Always | ✅ Optionally | Lazy-evaluated |
| `LazyProp` (`optional()`) | ❌ Never | ✅ Only if in `$only` | Must be explicitly requested |
| `AlwaysProp` (`always()`) | ✅ Always | ✅ Always | Included even in partial reloads |
| `DeferProp` (`defer()`) | ❌ Never (in `deferredProps`) | ✅ Separate XHR | Client fetches per group |
| `OnceProp` (`once()`) | ✅ First time | ❌ Skip if in `X-Inertia-Except-Once-Props` | Client caches; supports `.as()`, `.until()`, `.fresh()` |
| `MergeProp` (`merge()`) | ✅ Yes | ✅ Yes | Populates `mergeProps`/`prependProps`/`deepMergeProps` |

---

## Project structure

```
symfony-inertia-bundle/
├── AGENTS.md
├── CLAUDE.md
├── composer.json
├── config/
│   ├── definition.php          ← DefinitionConfigurator schema
│   └── services.yaml           ← explicit service definitions, no autowire
├── src/
│   ├── InertiaBundle.php       ← AbstractBundle: configure() + loadExtension()
│   ├── Service/
│   │   └── Inertia.php         ← render(), share(), shareOnce(), defer(), once(), version()
│   ├── Response/
│   │   └── InertiaResponse.php ← builds page object, handles HTML vs JSON
│   ├── EventListener/
│   │   └── InertiaListener.php ← kernel.request (detect, version check) + kernel.response (302→303)
│   ├── Props/
│   │   ├── LazyProp.php
│   │   ├── DeferProp.php
│   │   ├── OnceProp.php
│   │   └── MergeProp.php
│   ├── Twig/
│   │   └── InertiaTwigExtension.php   ← inertia(page), inertiaHead(page)
│   └── Controller/
│       └── AbstractInertiaController.php
└── tests/
    ├── Unit/
    └── Functional/
        └── Protocol/
            ├── FirstVisitTest.php
            ├── XhrVisitTest.php
            ├── AssetVersionTest.php
            ├── PartialReloadTest.php
            └── RedirectTest.php
```

---

## Testing standards

- PHPUnit 10.5+ or 11+, minimum **95% coverage** on `src/`
- Test naming: `test<Behavior>_<Condition>_<ExpectedResult>`
- `WebTestCase` for functional/protocol tests
- Always test both paths: Inertia XHR AND normal HTML request
- Check HTTP headers precisely with `assertResponseHasHeader()`
- Never test private methods directly — test through public API

---

## Git — commit format

```
feat(protocol): implement partial reload support
fix(listener): correct 302→303 conversion after DELETE
test(functional): add asset versioning 409 conflict test
refactor(response): extract page object builder
chore(ci): add Symfony 8.0 to test matrix
docs(readme): add Flex installation instructions
```

---

## Inertia v2 → v3 migration notes

**v3.0.0 released 2026-03-24.** This project currently targets v2. Mark future migration points with `// TODO: Inertia v3`:

| Area | v2 (current) | v3 |
|------|-------------|-----|
| HTML embedding | `<div id="app" data-page='...'>` attribute | `<script type="application/json">` tag |
| `clearHistory` | Always present, even if `false` | Omitted when `false` |
| `encryptHistory` | Always present, even if `false` | Omitted when `false` |
| `sharedProps` | Absent from page object | Added as metadata field |
| `X-Inertia-Redirect` | Absent | Added response header |

---

## Available slash commands

```
/plan   <feature>   → plan before coding (use in plan mode: Shift+Tab)
/tdd    <feature>   → TDD cycle: Red → Green → Refactor
/review             → full review: protocol + bundle conventions + coverage
```

## Workflow

```
Shift+Tab (plan mode) → /plan <feature> → review plan → /clear
Shift+Tab (work mode) → /tdd <feature> → /review → commit
```