# nytodev/inertia-bundle — Documentation

Symfony bundle implementing the [Inertia.js](https://inertiajs.com/) v2 server-side protocol.

## Table of contents

1. [Installation & configuration](installation.md)
2. [Rendering](rendering.md)
3. [Prop types](props.md)
4. [Asset versioning, redirects & history](features.md)
5. [Flash & validation errors](flash-errors.md)
6. [Server-Side Rendering (SSR)](ssr.md)
7. [Testing](testing.md)

## How Inertia works

Inertia is a protocol that lets you build single-page applications without building a separate API. The server renders a component name and props; the JavaScript adapter (React, Vue, Svelte…) mounts the matching component.

- **First visit** — the server returns a full HTML page with a `<div id="app" data-page='...'>` root element.
- **Subsequent visits** — the client sends `X-Inertia: true`; the server returns a JSON page object instead of HTML.
- **Partial reloads** — the client can request only a subset of props via `X-Inertia-Partial-Data`.

This bundle handles all protocol details (headers, 302→303 conversion, asset versioning 409, partial reloads, deferred/merge/once props) so your controllers just call `$inertia->render()`.

It is compatible with any frontend bundler: [vite-plugin-symfony](https://symfony-vite.pentatrion.com) or Webpack Encore.
