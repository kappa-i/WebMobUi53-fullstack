# TODO – Application de sondage

Échéance : **dimanche 17 mai 2026 à 23:59:59 UTC**

---

## Backend – Endpoints JSON

| Statut | Endpoint | Description |
|--------|----------|-------------|
| ✅ | `GET /api/v1/polls` | Liste des sondages de l'utilisateur connecté |
| ✅ | `GET /api/v1/polls/{token}` | Afficher un sondage par token — inclut `user_has_voted` |
| ✅ | `DELETE /api/v1/polls/{id}` | Supprimer un sondage (auth) |
| ✅ | `POST /api/v1/polls` | Créer un sondage (auth) |
| ✅ | `PUT /api/v1/polls/{id}` | Modifier un sondage (auth, propriétaire) |
| ✅ | `POST /api/v1/polls/{id}/start` | Démarrer un sondage |
| ✅ | `POST /api/v1/polls/{token}/votes` | Voter (auth) — unicité garantie |
| ✅ | `GET /api/v1/polls/{token}/results` | Résultats — protégé si privés |

---

## Frontend – Vue.js

### Dashboard

- [x] Charger les sondages via API (`fetchPolls` au montage — plus de Blade props)
- [x] Afficher la liste des sondages
- [x] Supprimer un sondage
- [x] Bouton "Démarrer" pour les sondages en brouillon
- [x] Bouton "Voter" (lien direct vers la page de vote) pour les sondages actifs
- [x] Bouton "Nouveau sondage"
- [x] Navigation `view = ref('list'|'create'|'edit')`
- [x] Afficher le statut (brouillon / actif / terminé) clairement
- [x] Gestion des erreurs (affichage messages)

### Formulaire de création / édition

- [x] Champ question (obligatoire) + titre (optionnel)
- [x] Gestion dynamique des options (ajout / modification / suppression)
- [x] Paramètre : choix multiple, résultats publics, durée, brouillon/lancement
- [x] Sauvegarde via `POST` (création) ou `PUT` (édition)
- [x] Validation côté frontend + affichage erreurs API

### Page de vote (`/polls/{token}`)

- [x] Entrypoint Vite + Blade view + route web
- [x] Charger le sondage via API
- [x] Afficher la question et les options
- [x] Voter (radio/checkbox selon config)
- [x] Bloquer le vote si brouillon ou terminé + message clair
- [x] Options grisées si déjà voté (détecté via `user_has_voted` au chargement)
- [x] Message "Connectez-vous pour voter" si non authentifié
- [x] Résultats via polling toutes les 5s + graphique barres CSS
- [x] Accès anonyme aux résultats si `results_public=true` uniquement

### Composants et composables

- [x] `PollForm.vue` — création + édition
- [x] `PollTable.vue` — tableau avec actions (démarrer, voter, modifier, supprimer)
- [x] `VoteForm.vue` — formulaire de vote avec état désactivé
- [x] `ResultsChart.vue` — barres CSS avec polling
- [x] `usePollStore.js` — `fetchPolls`, `createPoll`, `updatePoll`, `startPoll`, `deletePoll`

---

## UI / Qualité

- [x] Design responsive (mobile first) avec Tailwind CSS
- [x] États de chargement ("Chargement…")
- [x] Messages d'erreur utilisateur (formulaires, API)
- [x] Feedback de succès (vote soumis, sondage créé)
- [x] Cohérence visuelle entre dashboard et page de vote

---

## Documentation & Livraison

- [x] Mettre à jour `README.md` avec instructions d'installation claires
- [ ] Vérifier que le projet tourne from scratch (`php artisan migrate`, `npm run build`)
- [ ] Commit propre + push GitHub avant l'échéance

