# STEPS.md – Guide d'implémentation étape par étape

Ce fichier décrit comment implémenter chaque fonctionnalité du projet.
Il est mis à jour au fil de l'avancement.

---

## Backend

### ✅ GET /api/v1/polls — Liste des sondages
Déjà implémenté. `ApiPollController@index` retourne les sondages de l'utilisateur connecté.

### ✅ GET /api/v1/polls/{token} — Afficher un sondage par token
Déjà implémenté. `ApiPollController@show` charge le sondage avec ses options et le compte de votes par option.

### ✅ DELETE /api/v1/polls/{id} — Supprimer un sondage
Déjà implémenté. `ApiPollController@remove` vérifie la propriété avant de supprimer.

---

### ✅ Bouton "Résultats" — PollTable

Dans `PollTable.vue`, ajouter après le bouton "Voter" :
```html
<a v-if="pollStatus(poll) === 'terminé'" :href="'/polls/' + poll.secret_token" ...>Résultats</a>
```
- Visible uniquement quand le sondage est terminé (`pollStatus === 'terminé'`)
- Lien vers la page de vote qui affiche déjà les résultats

---

### ✅ Statut réactif au temps

Pour que le badge de statut et `isEnded` se mettent à jour automatiquement quand `ends_at` est atteint :

1. Ajouter `const now = ref(Date.now())` dans le composant
2. Démarrer un `setInterval` dans `onMounted` qui fait `now.value = Date.now()` toutes les 30s
3. Nettoyer avec `clearInterval` dans `onUnmounted`
4. Utiliser `now.value` dans la comparaison au lieu de `new Date()` :
   ```js
   new Date(poll.ends_at).getTime() < now.value  // ✅ réactif
   new Date(poll.ends_at) < new Date()            // ❌ non réactif
   ```
5. Appliquer dans `PollTable.vue` (fonction `pollStatus`) et `AppPollVote.vue` (computed `isEnded`)

---

### ✅ Poll model — `$casts`

Ajouter dans `app/Models/Poll.php` :
```php
protected $casts = [
    'is_draft'               => 'boolean',
    'allow_multiple_choices' => 'boolean',
    'allow_vote_change'      => 'boolean',
    'results_public'         => 'boolean',
    'started_at'             => 'datetime',
    'ends_at'                => 'datetime',
];
```
- Les casts `datetime` font que Laravel retourne les timestamps en ISO 8601 avec le suffixe `Z` (UTC), que JavaScript parse correctement
- Sans ces casts, les dates sont des chaînes brutes sans timezone → JavaScript les interprète en heure locale → décalage à l'affichage et dans `isEnded`

---

### ✅ POST /api/v1/polls — Créer un sondage

1. Dans `ApiPollController`, ajouter une méthode `store(Request $request)`
2. Valider les champs : `question` (required), `title` (nullable), `allow_multiple_choices` (bool), `results_public` (bool), `duration` (nullable integer), `is_draft` (bool), `options` (array, min 2 items, chaque item a un `label`)
3. Générer `secret_token` : `Str::random(32)` (importer `Illuminate\Support\Str`)
4. Créer le poll avec `$request->user()->polls()->create([...])`
5. Si `is_draft = false`, définir `started_at = now()` et calculer `ends_at = now()->addSeconds($duration)` si `duration` est présent
6. Créer les options en masse : `$poll->options()->createMany(...)` avec les labels reçus
7. Retourner le poll avec ses options en JSON (201)
8. Ajouter la route dans `api.php` dans le groupe `auth:sanctum` : `Route::post('/v1/polls', [ApiPollController::class, 'store'])`

---

### ✅ PUT /api/v1/polls/{id} — Modifier un sondage

1. Ajouter une méthode `update(Request $request, int $id)` dans `ApiPollController`
2. Vérifier que le poll appartient à l'utilisateur connecté (404 sinon)
3. Valider les mêmes champs que pour la création
4. Mettre à jour les champs scalaires du poll
5. Pour les options : stratégie sync — supprimer les options existantes non présentes dans le payload, mettre à jour celles avec un `id`, créer les nouvelles
6. Retourner le poll mis à jour avec ses options
7. Ajouter la route : `Route::put('/v1/polls/{id}', [ApiPollController::class, 'update'])` dans le groupe auth

---

### ✅ POST /api/v1/polls/{id}/start — Démarrer un sondage

1. Ajouter une méthode `start(Request $request, int $id)` dans `ApiPollController`
2. Vérifier la propriété du sondage
3. Vérifier que le sondage est encore en brouillon (`is_draft = true`), sinon retourner une erreur 422
4. Mettre à jour : `is_draft = false`, `started_at = now()`
5. Si `duration` est défini, calculer `ends_at = now()->addSeconds($poll->duration)`
6. Sauvegarder et retourner le poll mis à jour
7. Ajouter la route dans le groupe auth : `Route::post('/v1/polls/{id}/start', [ApiPollController::class, 'start'])`

---

### ✅ POST /api/v1/polls/{token}/votes — Voter

1. Ajouter une méthode `vote(Request $request, string $token)` dans `ApiPollController`
2. Charger le poll par token (404 si non trouvé)
3. Vérifier que le sondage est actif : `is_draft = false` et (`ends_at` est null ou dans le futur), sinon 422
4. Valider le payload : `option_ids` (array required) — ou `option_id` si choix unique
5. Si `allow_multiple_choices = false`, vérifier qu'un seul `option_id` est envoyé
6. Vérifier que tous les `option_ids` appartiennent bien à ce poll
7. Si `allow_multiple_choices = false` : vérifier qu'aucun vote de cet utilisateur n'existe déjà pour ce poll (unicité `poll_id + user_id`), sinon 422
8. Créer les votes : une ligne par option cochée dans `poll_votes`
9. Retourner 201 avec les votes créés
10. Ajouter la route (auth requise) : `Route::post('/v1/polls/{token}/votes', [ApiPollController::class, 'vote'])`

---

### ✅ GET /api/v1/polls/{token}/results — Résultats

1. Ajouter une méthode `results(Request $request, string $token)` dans `ApiPollController`
2. Charger le poll par token (404 si non trouvé)
3. Contrôle d'accès :
   - Si `results_public = true` → accessible à tous (même non authentifié)
   - Sinon, vérifier que l'utilisateur est authentifié ET propriétaire du poll, sinon 403
4. Charger les options avec le compte de votes : `$poll->options()->withCount('votes')->get()`
5. Retourner : `{ poll: {...}, options: [{ id, label, votes_count }], total_votes: int }`
6. Ajouter la route (sans middleware auth) : `Route::get('/v1/polls/{token}/results', [ApiPollController::class, 'results'])`

---


---

## Frontend

### ✅ Dashboard — Afficher la liste des sondages
Déjà implémenté via `PollTable.vue` et `usePollStore`.

### ✅ Dashboard — Supprimer un sondage
Déjà implémenté via `usePollStore.deletePoll`.

---

### ✅ Dashboard — Statut, bouton démarrer, lien de partage

1. Dans `PollTable.vue`, ajouter une colonne "Statut" :
   - Calculer le statut via `pollStatus(poll)` : `'brouillon'` / `'actif'` / `'terminé'`
   - Styler le badge via un objet `statusStyle` indexé par le statut (évite les `v-if` en cascade)
2. Bouton "Démarrer" : `v-if="poll.is_draft"`, appelle `startPoll(id)` du store
3. Bouton "Voter" : `<a :href="'/polls/' + poll.secret_token">` (lien direct, `v-if="!poll.is_draft"`)
4. Bouton "Lien" : copie l'URL dans le presse-papiers avec `navigator.clipboard.writeText(...)`, feedback 2s via `copiedId = ref(null)`
5. Bouton "Modifier" : émet `edit` avec l'objet poll complet vers `AppPollDashboard.vue`

---

### ✅ Formulaire création / édition — PollForm.vue

1. `resources/js/components/PollForm.vue` avec prop `poll` (null = création, objet = édition)
2. `isEdit = props.poll !== null` détermine le mode
3. En édition, les options sont pré-remplies avec leur `id` pour la sync backend
4. Validation locale : question non vide, au moins 2 options avec label non vide
5. Soumission : `createPoll()` ou `updatePoll()` selon le mode, puis `emit('saved')`
6. Navigation dans le dashboard via `view = ref('list'|'create'|'edit')` — pas de hash routing

---

### ✅ Page de vote — nouvelle app Vue

1. Entrypoint `resources/js/poll-vote.js` → déclarer dans `vite.config.js`
2. `resources/views/polls/vote.blade.php` → injecte `token` et `user` en `data-props`
3. Route web `GET /polls/{token}` (sans auth) — ATTENTION : doit être déclarée APRÈS le groupe auth, sinon elle intercepte `/polls/dashboard`
4. `AppPollVote.vue` : charge le sondage au montage, `user_has_voted` récupéré depuis la réponse API
5. Computed `canVote` : user connecté + `is_draft = false` + `ends_at` non dépassé
6. Computed `showResults` : `results_public = true` OU user est propriétaire (`user.id === poll.user_id`)
7. `VoteForm.vue` reçoit prop `disabled` → options grisées (`opacity-50`), bouton masqué
8. `ResultsChart.vue` : polling toutes les 5s via `usePolling`, barres CSS avec `transition-all`

---

### ✅ Affichage du statut — PollTable.vue

Statut calculé côté frontend dans `pollStatus(poll)` :
- `is_draft = true` → `'brouillon'` (badge jaune)
- `is_draft = false` et `ends_at` dans le passé → `'terminé'` (badge gris)
- `is_draft = false` et pas de `ends_at` ou dans le futur → `'actif'` (badge vert)

Badge stylé via un objet `statusStyle` indexé par le statut, appliqué avec `:class`.

---

### ✅ Formulaire de vote — VoteForm.vue

1. Créer `resources/js/components/VoteForm.vue`
2. Props : `poll` (objet complet avec options)
3. État local : `selected = ref([])` (tableau d'IDs d'options)
4. Si `allow_multiple_choices = false` : utiliser des `<input type="radio">`, sinon `<input type="checkbox">`
5. Validation : au moins une option sélectionnée
6. Soumission : `POST /api/v1/polls/{token}/votes` avec `{ option_ids: selected.value }`
7. Après succès : masquer le formulaire, afficher "Vote enregistré" et les résultats
8. Gérer l'erreur 422 (déjà voté) : afficher un message clair

---

### ✅ Résultats avec polling — ResultsChart.vue

1. `resources/js/components/ResultsChart.vue` — prop : `token`
2. `fetchResults()` appelé au montage puis via `usePolling` toutes les 5s
3. Barres CSS : `width` calculé en % du total, `transition-all duration-500` pour animer
4. Affiche le total de votes + pourcentage par option

---

### ✅ Étendre usePollStore.js

- `fetchPolls()` → `GET /api/v1/polls`, expose aussi `loading`
- `createPoll(data)` → `POST /api/v1/polls` → `unshift` dans `polls.value`
- `updatePoll(id, data)` → `PUT /api/v1/polls/{id}` → remplace en place dans `polls.value`
- `startPoll(id)` → `POST /api/v1/polls/{id}/start` → merge avec spread (`{ ...old, ...returned }`) pour conserver les options non retournées

---

### ✅ Design responsive

- Dashboard : `max-w-4xl mx-auto px-4` dans `AppPollDashboard.vue`
- Tableau `PollTable` : `overflow-x-auto` sur le wrapper + `min-w-[500px]` sur le `<table>` pour scroll horizontal sur mobile
- Page de vote : `min-h-screen bg-gray-50` + `max-w-xl mx-auto px-4 py-8`

---

### ✅ Gestion des erreurs — Dashboard

- `fetchError = ref(null)` dans `AppPollDashboard.vue`
- `onMounted` wrappé dans `try/catch` → en cas d'échec, `fetchError.value = '...'`
- Bandeau rouge affiché à la place du tableau si `fetchError` est défini
