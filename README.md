# Application de sondage — Laravel + Vue.js

Application web de sondage multi-plateforme (mobile first) développée avec Laravel 12 et Vue.js 3.

## Stack technique

- **Backend** : Laravel 12, PHP 8.2+, SQLite
- **Frontend** : Vue.js 3.4 (Composition API), Tailwind CSS v4, Vite
- **Auth** : Laravel Sanctum (SPA cookie — pas de Bearer token côté Vue)

## Installation

### Prérequis

- PHP >= 8.2
- Composer
- Node.js >= 18 et npm

### Étapes

```bash
# 1. Cloner le dépôt
git clone <url-du-repo>
cd <nom-du-repo>

# 2. Installer les dépendances
composer install
npm install

# 3. Configurer l'environnement
cp .env.example .env
php artisan key:generate
```

Dans `.env`, vérifier/adapter :
```
APP_URL=http://localhost:8000
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,127.0.0.1,127.0.0.1:8000
```

```bash
# 4. Migrer la base de données
php artisan migrate

# 5. Démarrer les serveurs
php artisan serve      # terminal 1
npm run dev            # terminal 2
```

L'application est accessible à **http://localhost:8000**.

## Fonctionnalités

### Dashboard (`/polls/dashboard`) — authentification requise
- Liste des sondages avec statut (brouillon / actif / terminé)
- Créer un sondage (question, options, paramètres, brouillon ou lancement immédiat)
- Modifier un sondage (question, options, paramètres)
- Supprimer un sondage
- Démarrer un sondage en brouillon
- Copier le lien de partage / accéder directement à la page de vote

### Page de vote (`/polls/{token}`) — accessible sans authentification
- Affichage de la question et des options
- Vote (choix unique ou multiple selon configuration)
- Options grisées si déjà voté
- Message clair si le sondage est terminé ou en brouillon
- Résultats en temps réel via polling (toutes les 5 secondes) avec graphique en barres
- Accès aux résultats conditionnel : public pour tous, privé uniquement pour le propriétaire

## Architecture frontend

Deux applications Vue.js distinctes, chacune montée sur sa propre page Blade :

| App | Entrypoint | Route |
|-----|-----------|-------|
| Dashboard | `poll-dashboard.js` | `/polls/dashboard` |
| Vote | `poll-vote.js` | `/polls/{token}` |

### Composants
- `PollTable.vue` — tableau des sondages avec actions
- `PollForm.vue` — formulaire création/édition (mode déterminé par la prop `poll`)
- `VoteForm.vue` — formulaire de vote (radio ou checkbox selon `allow_multiple_choices`)
- `ResultsChart.vue` — graphique en barres avec polling

### Store
`usePollStore.js` — singleton via `ref()` module-level (pas de Pinia). Expose : `fetchPolls`, `createPoll`, `updatePoll`, `startPoll`, `deletePoll`.

### Composables utilisés
- `useFetchApi` — wrapper fetch avec CSRF Sanctum
- `usePolling` — polling régulier pour les résultats en temps réel

## API JSON

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| GET | `/api/v1/polls` | ✅ | Liste des sondages |
| POST | `/api/v1/polls` | ✅ | Créer un sondage |
| PUT | `/api/v1/polls/{id}` | ✅ | Modifier un sondage |
| DELETE | `/api/v1/polls/{id}` | ✅ | Supprimer un sondage |
| POST | `/api/v1/polls/{id}/start` | ✅ | Démarrer un sondage |
| GET | `/api/v1/polls/{token}` | ❌ | Afficher un sondage |
| POST | `/api/v1/polls/{token}/votes` | ✅ | Voter |
| GET | `/api/v1/polls/{token}/results` | ❌ | Résultats (conditionnel) |
