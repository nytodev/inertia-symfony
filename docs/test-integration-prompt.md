# Prompt — Test d'intégration : Symfony 7.4 + Inertia.js + React + nytodev/inertia-bundle

## Objectif

Créer une application Symfony 7.4 fonctionnelle avec Inertia.js (React) et le bundle `nytodev/inertia-bundle`, vérifier que le protocole Inertia v2 fonctionne correctement de bout en bout.

---

## Prérequis

- PHP >= 8.2
- Composer
- Node.js >= 18 + npm
- Symfony CLI (`symfony check:requirements` doit passer)

---

## Étape 1 — Créer l'application Symfony 7.4

```bash
symfony new inertia-test --version="7.4.*" --webapp
cd inertia-test
```

Vérifier que l'app démarre :

```bash
symfony server:start -d
symfony open:local
```

---

## Étape 2 — Installer nytodev/inertia-bundle

```bash
composer require nytodev/inertia-bundle
```

Créer `config/packages/inertia.yaml` :

```yaml
inertia:
    root_view: base.html.twig
    version: null
    encrypt_history: false
```

---

## Étape 3 — Installer Webpack Encore

```bash
composer require symfony/webpack-encore-bundle
npm install
npm install @inertiajs/react react react-dom
npm install --save-dev @babel/plugin-transform-class-properties
```

Activer React dans `webpack.config.js` — remplacer le contenu par :

```js
const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.jsx')
    .enableReactPreset()
    .disableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
;

module.exports = Encore.getWebpackConfig();
```

---

## Étape 4 — Créer l'entry point JavaScript

Supprimer `assets/app.js` et créer `assets/app.jsx` :

```jsx
// assets/app.jsx
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'

createInertiaApp({
    resolve: name => require(`./Pages/${name}`),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />)
    },
})
```

Créer le dossier des pages React :

```bash
mkdir -p assets/Pages
```

---

## Étape 5 — Configurer le template Twig racine

Remplacer `templates/base.html.twig` :

```twig
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inertia Test</title>
    {% block stylesheets %}
        {{ encore_entry_link_tags('app') }}
    {% endblock %}
</head>
<body>
    {{ inertia(page) }}
    {% block javascripts %}
        {{ encore_entry_script_tags('app') }}
    {% endblock %}
</body>
</html>
```

---

## Étape 6 — Créer un contrôleur et des pages React

Créer `src/Controller/HomeController.php` :

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Nytodev\InertiaBundle\Service\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController
{
    #[Route('/', name: 'home')]
    public function index(Inertia $inertia): Response
    {
        return $inertia->render('Home', [
            'message' => 'Hello from Symfony + Inertia.js!',
            'user'    => ['name' => 'Alice', 'role' => 'admin'],
        ]);
    }

    #[Route('/about', name: 'about')]
    public function about(Inertia $inertia): Response
    {
        return $inertia->render('About', [
            'title' => 'About page',
        ]);
    }
}
```

Créer `assets/Pages/Home.jsx` :

```jsx
export default function Home({ message, user }) {
    return (
        <div>
            <h1>{message}</h1>
            <p>Logged in as: {user.name} ({user.role})</p>
            <a href="/about">Go to About</a>
        </div>
    )
}
```

Créer `assets/Pages/About.jsx` :

```jsx
export default function About({ title }) {
    return (
        <div>
            <h1>{title}</h1>
            <a href="/">Back to Home</a>
        </div>
    )
}
```

---

## Étape 7 — Builder les assets et lancer l'app

```bash
npm run dev
symfony server:start -d
```

---

## Vérifications à effectuer

### ✅ 1. Premier chargement (first visit)

Ouvrir `http://localhost:8000/` dans le navigateur.

**Attendu :**
- La page HTML contient `<div id="app" data-page='...'>`
- Le JSON dans `data-page` contient `{"component":"Home","props":{"message":"Hello from Symfony + Inertia.js!","user":{"name":"Alice","role":"admin"},...}}`
- React monte et affiche le contenu

Vérifier avec curl :

```bash
curl -s http://localhost:8000/ | grep "data-page"
```

### ✅ 2. Visite XHR (navigation Inertia)

```bash
curl -s http://localhost:8000/ \
  -H "X-Inertia: true" \
  -H "X-Inertia-Version: " \
  | python3 -m json.tool
```

**Attendu :**
- Status `200`
- Header `X-Inertia: true`
- Header `Content-Type: application/json`
- Header `Vary: X-Inertia`
- Body JSON : `{"component":"Home","props":{...},"url":"/","version":null,"clearHistory":false,"encryptHistory":false}`

### ✅ 3. Navigation SPA

Cliquer sur "Go to About" dans le navigateur.

**Attendu :**
- Pas de rechargement complet de la page
- L'URL change vers `/about`
- Le composant `About` s'affiche

### ✅ 4. Conversion 302 → 303

```bash
curl -s -o /dev/null -w "%{http_code}" -X POST http://localhost:8000/ \
  -H "X-Inertia: true"
```

Créer une route POST temporaire qui fait un `RedirectResponse('/about')` et vérifier que la réponse est `303` (pas `302`).

### ✅ 5. Props `errors` toujours présent

```bash
curl -s http://localhost:8000/ \
  -H "X-Inertia: true" \
  -H "X-Inertia-Version: " \
  | python3 -c "import sys,json; d=json.load(sys.stdin); print('errors' in d['props'])"
```

**Attendu :** `True`

### ✅ 6. Partial reload

```bash
curl -s http://localhost:8000/ \
  -H "X-Inertia: true" \
  -H "X-Inertia-Version: " \
  -H "X-Inertia-Partial-Data: message" \
  -H "X-Inertia-Partial-Component: Home" \
  | python3 -m json.tool
```

**Attendu :** `props` ne contient que `message` et `errors` (pas `user`).

### ✅ 7. Asset versioning (409)

Mettre `version: 'v1'` dans `config/packages/inertia.yaml`, relancer le serveur, puis :

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/ \
  -H "X-Inertia: true" \
  -H "X-Inertia-Version: OLD_VERSION"
```

**Attendu :** `409` + header `X-Inertia-Location: http://localhost:8000/`

---

## Résultat attendu

Toutes les vérifications passent. L'application affiche les composants React, la navigation SPA fonctionne sans rechargement, et le protocole Inertia v2 est respecté (headers, page object, partial reloads, 409).
