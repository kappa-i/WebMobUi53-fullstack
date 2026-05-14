# RECAP.md – Journal des implémentations

Ce fichier résume ce qui a été implémenté, pourquoi, et les choix techniques faits.
Il est mis à jour après chaque fonctionnalité terminée.

---

## État initial du projet (base fournie)

### Backend

**`GET /api/v1/polls`** — `ApiPollController@index`
Retourne les sondages de l'utilisateur connecté, triés par date de création décroissante.
Protégé par `auth:sanctum`.

**`GET /api/v1/polls/{token}`** — `ApiPollController@show`
Charge un sondage par `secret_token` avec ses options et le compte de votes par option via `withCount('votes')`. Accessible sans authentification.

**`DELETE /api/v1/polls/{id}`** — `ApiPollController@remove`
Supprime un sondage en vérifiant que l'utilisateur connecté en est le propriétaire. Protégé par `auth:sanctum`.

### Frontend

**Dashboard** (`/polls/dashboard`)
- `PollDashboardController` charge les sondages et les passe à la Blade view via eager loading PHP
- `polls/dashboard.blade.php` injecte les données dans `data-props` sur `#app`
- `AppPollDashboard.vue` reçoit les props et les place dans le store via `setPolls()`
- `PollTable.vue` affiche un tableau basique avec colonnes : ID, titre, question, brouillon, dates
- Bouton "Supprimer" fonctionnel via `usePollStore.deletePoll()` qui appelle l'API puis filtre le store local

**Composables disponibles**
- `useFetchApi` : wrapper fetch avec CSRF, headers JSON, timeout, gestion erreurs. Expose `fetchApi` (imperatif) et `fetchApiToRef` (réactif avec refs)
- `usePolling` : polling régulier vers une URL
- `useHashRoute` : routing par hash d'URL
- `useJsonStorage` : persistance localStorage

**Store**
- `usePollStore` : singleton via `ref()` module-level. Expose `polls`, `setPolls`, `deletePoll`

---

## Implémentations à venir

*(Ce fichier sera complété après chaque fonctionnalité)*

---

### ✅ POST /api/v1/polls — Création de sondage

**Fichiers modifiés :**
- `app/Http/Controllers/Api/v1/ApiPollController.php` — méthode `store()` ajoutée
- `app/Models/Poll.php` — `$fillable` défini
- `app/Models/PollOption.php` — `$fillable` défini
- `routes/api.php` — route `POST /v1/polls` ajoutée dans le groupe `auth:sanctum`
- `resources/js/stores/usePollStore.js` — `createPoll()` ajouté
- `resources/js/components/PollForm.vue` — créé
- `resources/js/AppPollDashboard.vue` — bouton "Nouveau sondage" + affichage conditionnel du formulaire

**Choix techniques :**
- `secret_token` généré avec `Str::random(32)` à la création, jamais modifiable ensuite
- Si `is_draft = false`, `started_at = now()` et `ends_at` calculé immédiatement si `duration` fourni
- Les options sont créées en masse via `createMany()` après la création du poll
- Validation Laravel : `question` obligatoire, `options` min 2 items avec `label` obligatoire
- Côté Vue : validation locale avant envoi (question vide, moins de 2 options remplies)
- `createPoll()` dans le store fait un `unshift` pour ajouter le nouveau poll en tête de liste sans recharger
- Le formulaire est affiché/masqué dans `AppPollDashboard.vue` via `showForm = ref(false)`, le `PollTable` est caché pendant la saisie

---

### ✅ PUT /api/v1/polls/{id} — Modification de sondage

**Fichiers modifiés :**
- `app/Http/Controllers/Api/v1/ApiPollController.php` — méthode `update()` ajoutée
- `routes/api.php` — route `PUT /v1/polls/{id}` dans le groupe `auth:sanctum`
- `resources/js/stores/usePollStore.js` — `updatePoll()` ajouté
- `resources/js/components/PollForm.vue` — prop `poll` ajoutée, mode édition géré
- `resources/js/components/PollTable.vue` — bouton "Modifier" ajouté, émet `edit`
- `resources/js/AppPollDashboard.vue` — navigation par `view` ref (`list/create/edit`)

**Choix techniques :**
- `PollForm` est réutilisé pour création et édition via la prop `poll` (null = création)
- `isEdit = props.poll !== null` détermine le mode et adapte le titre, le bouton et l'appel API
- En édition, les options existantes sont pré-remplies avec leur `id` pour permettre la sync côté backend
- Le backend supprime les options absentes du payload, met à jour celles avec un `id`, crée les nouvelles
- Navigation dans le dashboard via `view = ref('list'|'create'|'edit')` — simple et lisible sans router
- `PollTable` émet `edit` avec l'objet poll complet, le parent passe cet objet comme prop à `PollForm`

---

### ✅ POST /api/v1/polls/{id}/start — Démarrage de sondage

**Fichiers modifiés :**
- `app/Http/Controllers/Api/v1/ApiPollController.php` — méthode `start()` ajoutée
- `routes/api.php` — route `POST /v1/polls/{id}/start` dans le groupe `auth:sanctum`
- `resources/js/stores/usePollStore.js` — `startPoll()` ajouté
- `resources/js/components/PollTable.vue` — bouton "Démarrer" visible uniquement si `is_draft = true`

**Choix techniques :**
- Le bouton "Démarrer" est affiché avec `v-if="poll.is_draft"` — disparaît automatiquement après le démarrage
- Après succès, le store met à jour le poll en place via spread (`{ ...polls.value[index], ...started }`) pour conserver les options et autres champs non retournés par cet endpoint
- `ends_at` calculé côté backend uniquement si `duration` est défini, sinon null (sondage sans fin)

---

### ☐ POST /api/v1/polls/{token}/votes — Vote

> À remplir après implémentation

---

### ☐ GET /api/v1/polls/{token}/results — Résultats

> À remplir après implémentation

---

### ☐ Dashboard — Statut, démarrer, lien de partage

> À remplir après implémentation

---

### ☐ PollForm.vue — Formulaire création/édition

> À remplir après implémentation

---

### ☐ Page de vote — AppPollVote.vue

> À remplir après implémentation

---

### ☐ VoteForm.vue — Formulaire de vote

> À remplir après implémentation

---

### ☐ ResultsChart.vue — Résultats avec polling et graphique

> À remplir après implémentation

---

### ☐ (Bonus) PUT /api/v1/polls/{token}/votes — Modifier un vote

> À remplir après implémentation
