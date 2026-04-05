# Server-Side Rendering (SSR)

Server-Side Rendering pre-renders the initial page HTML on the server via a Node.js process. This improves perceived performance and enables search engine indexing.

This bundle is **build-tool agnostic** — it communicates with the SSR server over HTTP regardless of how the SSR bundle was built.

## Requirements

SSR requires `symfony/http-client` to communicate with the Node.js SSR server:

```bash
composer require symfony/http-client
```

## Setup

### Step 1 — Install React (or Vue/Svelte)

**With Webpack Encore:**

```bash
npm install react react-dom @inertiajs/react
```

```js
// webpack.config.js
Encore
    // ...
    .enableReactPreset()
```

**With vite-plugin-symfony:**

```bash
npm install react react-dom @inertiajs/react @vitejs/plugin-react
```

### Step 2 — Create an SSR entry point

```js
// assets/ssr.js
import { createInertiaApp } from '@inertiajs/react'
import { createServer } from '@inertiajs/react/server'
import ReactDOMServer from 'react-dom/server'

createServer(page =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        resolve: name => {
            const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true })
            return pages[`./Pages/${name}.jsx`]
        },
        setup: ({ App, props }) => <App {...props} />,
    })
)
```

### Step 3 — Build the SSR bundle

**With Webpack Encore** — add a custom webpack config for the server target:

```js
// webpack.ssr.config.js
const Encore = require('@symfony/webpack-encore')

Encore
    .setOutputPath('public/build/ssr/')
    .setPublicPath('/build/ssr')
    .addEntry('ssr', './assets/ssr.js')
    .enableReactPreset()
    .disableSingleRuntimeChunk()

const config = Encore.getWebpackConfig()
config.target = 'node'
config.name = 'ssr'

module.exports = config
```

```bash
npx webpack --config webpack.ssr.config.js
```

**With vite-plugin-symfony** — create a separate config file for the SSR build:

```js
// vite.ssr.config.js
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import symfonyPlugin from 'vite-plugin-symfony'

export default defineConfig({
    plugins: [react(), symfonyPlugin()],
    build: {
        ssr: 'assets/ssr.js',
        outDir: 'public/build/ssr',
        rollupOptions: {
            output: { format: 'cjs' },
        },
    },
})
```

```bash
npx vite build -c vite.ssr.config.js
```

### Step 4 — Enable SSR in the bundle config

```yaml
# config/packages/inertia.yaml
inertia:
    ssr_enabled: true
    ssr_url: 'http://127.0.0.1:13714'
    ssr_bundle: 'public/build/ssr/ssr.js'   # path to the built SSR entry point
```

### Step 5 — Start the SSR server

```bash
php bin/console inertia:start-ssr
```

## Commands

| Command | Description |
|---------|-------------|
| `inertia:start-ssr` | Start the Node.js SSR server |
| `inertia:stop-ssr` | Stop the SSR server |
| `inertia:check-ssr` | Check if the SSR server is running and reachable |

> `inertia:stop-ssr` and `inertia:check-ssr` are only registered in the container when `ssr_enabled: true`.

## Twig template

No changes needed — `inertia(page)` and `inertiaHead(page)` automatically use SSR output when available:

```twig
<head>
    {{ inertiaHead(page) }}   {# outputs SSR <head> tags #}
</head>
<body>
    {{ inertia(page) }} {# outputs pre-rendered HTML instead of empty <div> #}
</body>
```

If the SSR server is unreachable, the bundle falls back to standard client-side rendering silently.

## Production

Manage the Node.js SSR process with a process manager:

```ini
; /etc/supervisor/conf.d/inertia-ssr.conf
[program:inertia-ssr]
command=node /var/www/app/public/build/ssr/ssr.js
autostart=true
autorestart=true
stdout_logfile=/var/log/inertia-ssr.log
stderr_logfile=/var/log/inertia-ssr-error.log
```
