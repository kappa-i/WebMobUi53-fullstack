# TODO – Application de sondage

Échéance : **dimanche 17 mai 2026 à 23:59:59 UTC**

---

## Backend – Endpoints JSON manquants

| Statut | Endpoint | Description |
|--------|----------|-------------|
| ✅ | `GET /api/v1/polls` | Liste des sondages de l'utilisateur connecté |
| ✅ | `GET /api/v1/polls/{token}` | Afficher un sondage par token (public) |
| ✅ | `DELETE /api/v1/polls/{id}` | Supprimer un sondage (auth) |
| ✅ | `POST /api/v1/polls` | Créer un sondage (auth) |
| ✅ | `PUT /api/v1/polls/{id}` | Modifier un sondage (auth, propriétaire) |
| ☐ | `POST /api/v1/polls/{id}/start` | Démarrer un sondage (auth, proprio) — set `is_draft=false`, `started_at=now`, calcul `ends_at` si `duration` |
| ☐ | `POST /api/v1/polls/{token}/votes` | Voter sur un sondage (auth) — valider unicité si choix unique |
| ☐ | `GET /api/v1/polls/{token}/results` | Résultats d'un sondage — protéger si `results_public=false` et non-propriétaire |
| ☐ | `PUT /api/v1/polls/{token}/votes` | *(Bonus)* Modifier un vote si `allow_vote_change=true` |

### Règles métier backend à implémenter

- [ ] Générer `secret_token` unique à la création (ex. `Str::random(32)`)
- [ ] Vérifier que l'utilisateur est propriétaire pour `PUT`, `DELETE`, `start`
- [ ] Calculer `ends_at = started_at + duration` lors du démarrage si `duration` est défini
- [ ] Refuser le vote si `is_draft=true`, ou si `ends_at` est dépassé
- [ ] Garantir l'unicité du vote par utilisateur pour les sondages à **choix unique** (unique sur `poll_id + user_id`)
- [ ] Retourner 403 si accès aux résultats d'un sondage non-public par un non-propriétaire non authentifié

---

## Frontend – Vue.js

### Dashboard (`poll-dashboard.js` / `AppPollDashboard.vue`)

- [x] Afficher la liste des sondages (tableau basique)
- [x] Supprimer un sondage
- [ ] Afficher le statut (brouillon / actif / terminé) clairement
- [ ] Bouton "Démarrer" pour les sondages en brouillon
- [ ] Bouton "Copier le lien de partage" (token) pour chaque sondage
- [ ] Lien vers la page d'édition
- [x] Bouton "Nouveau sondage"
- [ ] Gestion des erreurs (affichage messages)

### Formulaire de création / édition

- [ ] Champ question (obligatoire)
- [ ] Champ titre (optionnel)
- [ ] Gestion dynamique des options (ajout / modification / suppression)
- [ ] Paramètre : choix multiple (`allow_multiple_choices`)
- [ ] Paramètre : résultats publics (`results_public`)
- [ ] Paramètre : durée (`duration` en secondes, ou saisie en minutes/heures)
- [ ] Paramètre : lancement immédiat ou brouillon (`is_draft`)
- [ ] *(Bonus)* Paramètre : autoriser changement de vote (`allow_vote_change`)
- [ ] Sauvegarde via `POST` (création) ou `PUT` (édition)
- [ ] Validation côté frontend (min. 2 options, question non vide)

### Page de vote (accessible via token dans l'URL)

- [ ] Nouveau entrypoint Vite + nouvelle Blade view + route web
- [ ] Charger le sondage via `GET /api/v1/polls/{token}`
- [ ] Afficher la question et les options
- [ ] Permettre de voter (checkbox unique ou multiple selon config)
- [ ] Soumettre le vote via `POST /api/v1/polls/{token}/votes`
- [ ] Bloquer le vote si sondage terminé (`ends_at` dépassé) + message clair
- [ ] Bloquer le vote si brouillon
- [ ] *(Bonus)* Permettre de modifier le vote si `allow_vote_change=true`
- [ ] Afficher les résultats via polling (`usePolling`) si autorisé
- [ ] Graphique des résultats (barres ou camembert – chart libre)
- [ ] Accès anonyme aux résultats si `results_public=true` uniquement

### Composants et composables

- [x] Composant `PollForm.vue` – formulaire création (édition à compléter)
- [ ] Composant `PollCard.vue` ou amélioration de `PollTable.vue` – dashboard
- [ ] Composant `VoteForm.vue` – formulaire de vote
- [ ] Composant `ResultsChart.vue` – graphique résultats
- [ ] Composable `usePoll.js` – chargement + gestion d'un sondage par token
- [ ] Composable `useVote.js` – soumission du vote
- [x] Étendre `usePollStore.js` – ajout `createPoll` ✅ (`updatePoll`, `startPoll` à faire)

---

## UI / Qualité

- [ ] Design responsive (mobile first) avec Tailwind CSS
- [ ] États de chargement (spinner / skeleton)
- [ ] Messages d'erreur utilisateur (formulaires, API)
- [ ] Feedback de succès (vote soumis, sondage créé, lien copié)
- [ ] Cohérence visuelle entre dashboard et page de vote

---

## Documentation & Livraison

- [ ] Mettre à jour `README.md` avec instructions d'installation claires
- [ ] Documenter les choix d'architecture (composants, store, composables)
- [ ] Vérifier que le projet tourne from scratch (`php artisan migrate`, `npm run build`)
- [ ] Commit propre + push GitHub avant l'échéance

---

## Bonus

- [ ] Permettre de modifier un vote si `allow_vote_change=true` (frontend + API)
