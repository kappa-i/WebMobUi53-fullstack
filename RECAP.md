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

### ✅ Bouton "Résultats" pour les sondages terminés — PollTable

**Fichiers modifiés :**
- `resources/js/components/PollTable.vue` — bouton "Résultats" ajouté dans la colonne Actions

**Choix techniques :**
- Bouton affiché en plus du bouton "Voter" uniquement quand `pollStatus(poll) === 'terminé'`
- Lien vers `/polls/{token}` — même page que le vote, qui affiche déjà les résultats automatiquement

---

### ✅ Statut réactif au temps — PollTable + AppPollVote

**Fichiers modifiés :**
- `resources/js/components/PollTable.vue` — `now = ref(Date.now())` + `setInterval` 30s
- `resources/js/AppPollVote.vue` — même pattern pour `isEnded`

**Choix techniques :**
- `pollStatus()` et `isEnded` comparaient `new Date(poll.ends_at)` à `new Date()` — `new Date()` n'est pas réactif, Vue ne le retrace pas et ne re-rend pas quand le temps passe
- Solution : `now = ref(Date.now())` mis à jour toutes les 30s via `setInterval`. Vue trace `now.value` comme dépendance → re-rend automatiquement quand il change
- `setInterval` démarré dans `onMounted`, nettoyé dans `onUnmounted` pour éviter les fuites mémoire
- 30s de délai maximal avant mise à jour — suffisant pour un badge de statut

---

### ✅ Poll model — `$casts` datetime

**Fichiers modifiés :**
- `app/Models/Poll.php` — `$casts` ajouté pour les champs booléens et les timestamps

**Choix techniques :**
- Sans cast, Laravel retourne `ends_at` comme chaîne brute `"2026-05-14 17:00:00"` (sans timezone). JavaScript l'interprète comme heure locale au lieu d'UTC → décalage affiché
- Avec `'ends_at' => 'datetime'`, Laravel sérialise via Carbon : `"2026-05-14T17:00:00.000000Z"` — le `Z` indique UTC sans ambiguïté
- Les casts booléens (`is_draft`, `allow_multiple_choices`, etc.) garantissent que ces champs sont retournés comme `true`/`false` et non comme `0`/`1`

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

### ✅ POST /api/v1/polls/{token}/votes + GET /api/v1/polls/{token}/results — Vote & Résultats

**Fichiers créés / modifiés :**
- `app/Http/Controllers/Api/v1/ApiPollController.php` — méthodes `vote()` et `results()` ajoutées
- `app/Models/PollVote.php` — `$fillable` défini
- `routes/api.php` — routes `POST /votes` (auth) et `GET /results` (public conditionnel)
- `routes/web.php` — route `GET /polls/{token}` accessible sans auth
- `resources/views/polls/vote.blade.php` — créé
- `resources/js/poll-vote.js` — entrypoint créé
- `resources/js/AppPollVote.vue` — créé
- `resources/js/components/VoteForm.vue` — créé
- `resources/js/components/ResultsChart.vue` — créé
- `vite.config.js` — `poll-vote.js` ajouté aux inputs

**Choix techniques :**
- La page de vote est accessible sans auth — le user est passé via props Blade (null si non connecté)
- `canVote` est une computed qui combine : user connecté + sondage actif + pas encore voté
- Unicité du vote garantie côté API : on supprime les votes existants avant d'insérer (permet aussi le changement de vote si `allow_vote_change`)
- `results()` ne nécessite pas d'auth mais vérifie `results_public` ou propriété — retourne 403 sinon
- `ResultsChart` utilise `usePolling` toutes les 5 secondes et affiche des barres CSS (pas de librairie externe)
- `showResults` est une computed : visible si `results_public` ou si l'utilisateur est le propriétaire

---

### ✅ Dashboard — Actions, chargement via API, bouton voter

**Fichiers modifiés :**
- `app/Http/Controllers/PollDashboardController.php` — ne charge plus les polls (vue vide)
- `resources/views/polls/dashboard.blade.php` — `#app` sans `data-props`
- `resources/js/poll-dashboard.js` — mount sans props
- `resources/js/AppPollDashboard.vue` — `fetchPolls()` au `onMounted`, navigation par `view` ref
- `resources/js/stores/usePollStore.js` — `fetchPolls()` + `loading` ajoutés
- `resources/js/components/PollTable.vue` — boutons Démarrer, Voter (`<a>`), Modifier, Supprimer

**Choix techniques :**
- Chargement via API au lieu de Blade props — le dashboard est une vraie SPA
- Bouton "Voter" est un `<a :href>` simple — plus propre qu'un `window.location` JS
- `loading` exposé par le store permet d'afficher "Chargement…" pendant le fetch initial
- Statut calculé côté frontend par `pollStatus(poll)` : `brouillon` / `actif` / `terminé` selon `is_draft` et `ends_at` comparé à `new Date()`
- Badge coloré via objet `statusStyle` indexé par le statut — pas de `v-if` en cascade, plus lisible

---

### ✅ PollForm.vue — Formulaire création/édition

**Fichiers modifiés :**
- `resources/js/components/PollForm.vue` — prop `poll` (null = création, objet = édition)

**Choix techniques :**
- Un seul composant pour les deux modes via `isEdit = props.poll !== null`
- En édition, options pré-remplies avec leur `id` pour la sync backend (update/delete/create)
- Le mode brouillon/lancement immédiat est masqué en édition (non pertinent après création)

---

### ✅ Bouton "Copier le lien", gestion erreurs, responsive, README

**Fichiers modifiés :**
- `resources/js/components/PollTable.vue` — bouton "Lien" avec feedback "Copié !"
- `resources/js/AppPollDashboard.vue` — `fetchError` ref + bandeau d'erreur rouge
- `resources/js/AppPollDashboard.vue` — `max-w-4xl mx-auto` pour responsive
- `resources/js/AppPollVote.vue` — `min-h-screen bg-gray-50 max-w-xl mx-auto` pour responsive
- `README.md` — réécrit avec installation, fonctionnalités, architecture, API

**Choix techniques :**
- `copiedId = ref(null)` avec `setTimeout(..., 2000)` pour le feedback du bouton "Lien" — évite un état booléen par poll
- `overflow-x-auto` sur le wrapper du tableau + `min-w-[500px]` sur `<table>` — le tableau reste lisible sur mobile avec scroll horizontal plutôt que de se compresser
- `fetchError` affiché à la place du tableau entier — pas de contenu partiel en cas d'échec du chargement initial
- Bouton "Voter" implémenté comme `<a :href>` (pas JS) — navigation native, pas d'effet de bord avec l'historique

---

### ✅ Page de vote — AppPollVote.vue + VoteForm.vue + ResultsChart.vue

**Fichiers créés :**
- `resources/views/polls/vote.blade.php`
- `resources/js/poll-vote.js`
- `resources/js/AppPollVote.vue`
- `resources/js/components/VoteForm.vue`
- `resources/js/components/ResultsChart.vue`
- `vite.config.js` — `poll-vote.js` ajouté

**Choix techniques :**
- `user_has_voted` ajouté dans `ApiPollController@show` (via `$request->user()?->id`) — permet de griser les options dès le chargement si l'utilisateur a déjà voté
- `canVote` computed : user connecté + sondage actif + non terminé
- `VoteForm` reçoit prop `disabled` — options avec `opacity-50`, inputs `disabled`, bouton masqué
- `ResultsChart` appelle `fetchResults()` au montage puis via `usePolling` toutes les 5s
- Barres CSS avec `transition-all` pour animer les pourcentages en temps réel

---

