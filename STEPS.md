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

### ☐ POST /api/v1/polls/{id}/start — Démarrer un sondage

1. Ajouter une méthode `start(Request $request, int $id)` dans `ApiPollController`
2. Vérifier la propriété du sondage
3. Vérifier que le sondage est encore en brouillon (`is_draft = true`), sinon retourner une erreur 422
4. Mettre à jour : `is_draft = false`, `started_at = now()`
5. Si `duration` est défini, calculer `ends_at = now()->addSeconds($poll->duration)`
6. Sauvegarder et retourner le poll mis à jour
7. Ajouter la route dans le groupe auth : `Route::post('/v1/polls/{id}/start', [ApiPollController::class, 'start'])`

---

### ☐ POST /api/v1/polls/{token}/votes — Voter

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

### ☐ GET /api/v1/polls/{token}/results — Résultats

1. Ajouter une méthode `results(Request $request, string $token)` dans `ApiPollController`
2. Charger le poll par token (404 si non trouvé)
3. Contrôle d'accès :
   - Si `results_public = true` → accessible à tous (même non authentifié)
   - Sinon, vérifier que l'utilisateur est authentifié ET propriétaire du poll, sinon 403
4. Charger les options avec le compte de votes : `$poll->options()->withCount('votes')->get()`
5. Retourner : `{ poll: {...}, options: [{ id, label, votes_count }], total_votes: int }`
6. Ajouter la route (sans middleware auth) : `Route::get('/v1/polls/{token}/results', [ApiPollController::class, 'results'])`

---

### ☐ (Bonus) PUT /api/v1/polls/{token}/votes — Modifier un vote

1. Vérifier que `allow_vote_change = true` sur le poll, sinon 403
2. Supprimer les votes existants de l'utilisateur pour ce poll
3. Recréer les nouveaux votes comme dans `vote()`
4. Retourner 200 avec les nouveaux votes

---

## Frontend

### ✅ Dashboard — Afficher la liste des sondages
Déjà implémenté via `PollTable.vue` et `usePollStore`.

### ✅ Dashboard — Supprimer un sondage
Déjà implémenté via `usePollStore.deletePoll`.

---

### ☐ Dashboard — Statut, bouton démarrer, lien de partage

1. Dans `PollTable.vue` (ou un nouveau `PollCard.vue`), ajouter une colonne/section "Statut" :
   - brouillon → badge "Brouillon"
   - actif (non brouillon, ends_at null ou futur) → badge "Actif"
   - terminé (ends_at dans le passé) → badge "Terminé"
2. Ajouter un bouton "Démarrer" visible uniquement si `is_draft = true` :
   - Au clic, appeler `POST /api/v1/polls/{id}/start` via `fetchApi`
   - Mettre à jour le poll dans le store après succès
3. Ajouter un bouton "Copier le lien" :
   - Construire l'URL : `window.location.origin + '/polls/' + poll.secret_token`
   - Copier avec `navigator.clipboard.writeText(...)`
   - Afficher un feedback "Lien copié !"
4. Ajouter un bouton "Modifier" → navigation vers le formulaire d'édition

---

### ☐ Formulaire création / édition — PollForm.vue

1. Créer `resources/js/components/PollForm.vue`
2. Props : `poll` (null pour création, objet pour édition)
3. État local : `form = reactive({ title, question, options: [], allow_multiple_choices, results_public, duration, is_draft })`
4. Gestion des options :
   - Tableau de `{ id?: number, label: string }`
   - Bouton "Ajouter une option" → push un objet vide
   - Bouton "Supprimer" sur chaque option (minimum 2)
5. Validation avant soumission : question non vide, au moins 2 options avec label non vide
6. Soumission :
   - Création : `POST /api/v1/polls` → ajouter au store → rediriger vers dashboard
   - Édition : `PUT /api/v1/polls/{id}` → mettre à jour dans le store
7. Afficher les erreurs API retournées (ex: validation Laravel)
8. Intégrer le formulaire dans l'app dashboard via `useHashRoute` (ex: `#create`, `#edit/{id}`)

---

### ☐ Page de vote — nouvelle app Vue

1. Créer l'entrypoint `resources/js/poll-vote.js`
2. Créer `resources/js/AppPollVote.vue`
3. Déclarer dans `vite.config.js` → `input: [..., 'resources/js/poll-vote.js']`
4. Créer la Blade view `resources/views/polls/vote.blade.php` avec `<x-vue-app-layout>`
5. Ajouter la route web dans `routes/web.php` : `Route::get('/polls/{token}', ...)` (accessible sans auth)
6. Dans `AppPollVote.vue` :
   - Lire le token depuis les props Blade ou depuis `window.location.pathname`
   - Au montage, charger le sondage via `GET /api/v1/polls/{token}`
   - Afficher question + options
7. Contrôle d'affichage :
   - Si `is_draft = true` → message "Ce sondage n'est pas encore disponible"
   - Si `ends_at` dépassé → message "Ce sondage est terminé, il n'est plus possible de voter"
   - Sinon → afficher le formulaire de vote

---

### ☐ Formulaire de vote — VoteForm.vue

1. Créer `resources/js/components/VoteForm.vue`
2. Props : `poll` (objet complet avec options)
3. État local : `selected = ref([])` (tableau d'IDs d'options)
4. Si `allow_multiple_choices = false` : utiliser des `<input type="radio">`, sinon `<input type="checkbox">`
5. Validation : au moins une option sélectionnée
6. Soumission : `POST /api/v1/polls/{token}/votes` avec `{ option_ids: selected.value }`
7. Après succès : masquer le formulaire, afficher "Vote enregistré" et les résultats
8. Gérer l'erreur 422 (déjà voté) : afficher un message clair

---

### ☐ Résultats avec polling — ResultsChart.vue

1. Créer `resources/js/components/ResultsChart.vue`
2. Props : `token` (string), `poll` (pour accéder à `results_public`)
3. Au montage, démarrer le polling avec `usePolling` :
   - Appeler `GET /api/v1/polls/{token}/results` toutes les X secondes (ex: 5s)
   - Stocker le résultat dans un `ref`
4. Afficher les résultats sous forme de graphique :
   - Option simple : barres HTML/CSS (divs avec largeur en %)
   - Option avec librairie : Chart.js ou autre
5. Afficher le total de votes
6. Si `results_public = false` et utilisateur non propriétaire → ne pas afficher / afficher message "Résultats privés"
7. Arrêter le polling si `ends_at` est dépassé (inutile de continuer)

---

### ☐ Composable usePoll.js

1. Créer `resources/js/composables/usePoll.js`
2. Exporter une fonction `usePoll(token)` qui :
   - Charge le sondage via `fetchApiToRef({ url: '/polls/' + token })`
   - Retourne `{ poll, loading, error }`
3. Utilisé dans `AppPollVote.vue` pour charger les données initiales

---

### ☐ Étendre usePollStore.js

Ajouter dans `usePollStore.js` :
- `createPoll(data)` → `POST /api/v1/polls` → push dans `polls.value`
- `updatePoll(id, data)` → `PUT /api/v1/polls/{id}` → remplacer dans `polls.value`
- `startPoll(id)` → `POST /api/v1/polls/{id}/start` → mettre à jour `is_draft` et `started_at` dans le store
