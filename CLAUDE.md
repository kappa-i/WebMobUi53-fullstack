# CLAUDE.md – Contexte technique du projet

## Stack technique

- **Backend** : Laravel 12+, PHP 8.2+
- **Frontend** : Vue.js 3.4+, Composition API (`<script setup>`)
- **CSS** : Tailwind CSS v4 (via `@tailwindcss/vite`)
- **Build** : Vite + `laravel-vite-plugin`
- **Base de données** : SQLite (fichier `database/database.sqlite`)
- **Auth** : Laravel Sanctum – mode SPA cookie (session cookie, pas de Bearer token côté Vue)

---

## Architecture frontend

Les apps Vue sont embarquées dans des pages Blade. Chaque feature a son propre entrypoint Vite.

### Entrypoints existants

| Fichier JS | Vue root | Blade view |
|---|---|---|
| `resources/js/poll-dashboard.js` | `AppPollDashboard.vue` | `resources/views/polls/dashboard.blade.php` |

### Ajouter un nouvel entrypoint Vue

1. Créer `resources/js/<nom>.js` (importer `./bootstrap` en premier, puis monter l'app)
2. Créer `resources/js/App<Nom>.vue`
3. Déclarer l'entrypoint dans `vite.config.js` > `input`
4. Créer la Blade view avec `<x-vue-app-layout>` ou `<x-default-layout>`
5. Ajouter la route web dans `routes/web.php`

### Layouts Blade disponibles

- `<x-vue-app-layout>` – layout minimal (pas de nav/footer), pour les SPA Vue full-page
- `<x-default-layout>` – layout complet avec header/footer Laravel

### Props Blade → Vue

Les données serveur sont passées via `data-props` sur `#app` :

```blade
<div id="app" data-props='@json(["polls" => $polls])'></div>
```

Dans l'entrypoint JS :
```js
const props = JSON.parse(document.getElementById('app').dataset.props ?? '{}');
createApp(App).mount('#app');
// ou passer les props au composant root via defineProps
```

---

## Authentification & CSRF

- Auth via cookie de session Laravel (Sanctum SPA). Aucun token Bearer côté Vue.
- `resources/js/bootstrap.js` lit le cookie `XSRF-TOKEN` et l'injecte dans les headers via `setDefaultHeaders`.
- **Toujours importer `./bootstrap` en premier** dans chaque entrypoint Vue.
- L'URL de base de l'API est définie dans `bootstrap.js` : `/api/v1`.
- Les routes web protégées sont dans le groupe `auth` de `routes/web.php`.

---

## Composables disponibles

| Composable | Rôle |
|---|---|
| `useFetchApi` | Fetch vers l'API JSON avec CSRF + headers. Expose `fetchApi(options)` et `fetchApiToRef(options)`. |
| `useFetchJson` | Fetch JSON générique |
| `usePolling` | Polling régulier vers une URL – pour les résultats en temps réel |
| `useHashRoute` | Routing simple via hash de l'URL |
| `useJsonStorage` | Persistence localStorage |

### Utilisation de `useFetchApi`

```js
const { fetchApi, fetchApiToRef } = useFetchApi();

// Appel impératif
await fetchApi({ url: '/polls', method: 'GET' });
await fetchApi({ url: '/polls', data: { question: '...' } }); // POST par défaut si data présent

// Appel réactif (retourne des refs)
const { data, error, loading, fetchNow } = fetchApiToRef({ url: '/polls' });
```

---

## Store

`resources/js/stores/usePollStore.js` – store manuel (pas Pinia) basé sur `ref()` module-level.

Pattern : singleton partagé via import ES module. Toujours appeler `usePollStore()` pour accéder aux refs.

---

## Modèles Eloquent & schéma

### `polls`
| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `user_id` | FK users | Propriétaire |
| `title` | string nullable | |
| `question` | string | Obligatoire |
| `secret_token` | string unique | Lien de partage |
| `is_draft` | boolean | `true` = brouillon |
| `allow_multiple_choices` | boolean | |
| `allow_vote_change` | boolean | Bonus |
| `results_public` | boolean | |
| `duration` | uint nullable | En secondes |
| `started_at` | timestamp nullable | Calculé au lancement |
| `ends_at` | timestamp nullable | `started_at + duration` |

### `poll_options`
`id`, `poll_id` (FK), `label`

### `poll_votes`
`id`, `poll_id` (FK), `user_id` (FK), `poll_option_id` (FK)

---

## Routes API existantes

| Méthode | Route | Auth | Contrôleur |
|---|---|---|---|
| GET | `/api/v1/polls` | Sanctum | `ApiPollController@index` |
| GET | `/api/v1/polls/{token}` | Non | `ApiPollController@show` |
| DELETE | `/api/v1/polls/{id}` | Sanctum | `ApiPollController@remove` |

### Routes API à créer

| Méthode | Route | Auth |
|---|---|---|
| POST | `/api/v1/polls` | Sanctum |
| PUT | `/api/v1/polls/{id}` | Sanctum (proprio) |
| POST | `/api/v1/polls/{id}/start` | Sanctum (proprio) |
| POST | `/api/v1/polls/{token}/votes` | Sanctum |
| GET | `/api/v1/polls/{token}/results` | Conditionnel |
| PUT | `/api/v1/polls/{token}/votes` | Sanctum (bonus) |

---

## Conventions de code

- **Composition API** uniquement (`<script setup>`), pas d'Options API
- **Pas de Pinia** – store manuel via `ref()` module-level (pattern déjà en place)
- **Pas de Vue Router** – navigation via `useHashRoute` ou pages Blade séparées
- **Tailwind** pour tout le CSS, pas de styles inline
- **Pas de commentaires** sauf si logique non évidente
- Nommer les composables `use<Nom>.js`, les composants `<Nom>.vue` en PascalCase
- Les erreurs API sont des objets `{ status, statusText, data }` (format de `useFetchApi`)

---

## Commandes utiles

```bash
# Démarrer le serveur de dev
php artisan serve
npm run dev

# Build production
npm run build

# Migrations
php artisan migrate
php artisan migrate:fresh  # reset complet

# Lancer les tests
php artisan test
```
