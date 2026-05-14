cd w# Travail pratique : Créer une application de sondage (Laravel + Vue.js)

## Introduction

Ce projet vous invite à créer une application complète mêlant backend Laravel et frontend Vue.js,
sous la forme d'un système de sondage multi-plateforme.

L'objectif est de concevoir une interface permettant de créer, configurer, consulter et utiliser des
sondages à travers une API JSON consommée par le frontend.

Dans cette application, un sondage est un objet créé par une personne authentifiée, contenant une
question, plusieurs options de réponse et un ensemble de paramètres définissant son comportement
(brouillon ou lancé, choix simple ou multiple, visibilité des résultats et éventuelle durée de
 disponibilité).

Les modèles Eloquent de base sont fournis. Le travail portera principalement sur :

- le frontend Vue.js
- les endpoints backend JSON nécessaires au fonctionnement du frontend

Le système de sondage attendu repose notamment sur les fonctionnalités UI suivantes :

- afficher à la personne connectée la liste de ses sondages
- permettre à la personne connectée de créer, modifier et supprimer ses sondages
- permettre de définir la question, les options de réponse et les paramètres du sondage
- créer un sondage en mode brouillon, puis permettre de le démarrer soit au moment de sa création,
  soit plus tard
- permettre de configurer si le sondage accepte un choix simple ou plusieurs choix
- permettre de configurer si les résultats sont publics ou non
- permettre de configurer une durée de disponibilité du sondage
- permettre au créateur d'un sondage d'obtenir facilement le lien de partage contenant le token
- afficher une page de vote accessible via un lien contenant un token dans l'URL
- permettre à une personne authentifiée ayant reçu ce lien de voter au sondage
- permettre à une personne non authentifiée ayant reçu ce lien de consulter les résultats si, et
  seulement si, leur visibilité est publique
- afficher sur la page de vote les résultats en direct, via un polling régulier vers l'API
- afficher sur la page de vote un aperçu graphique des résultats ; le type de graphique est libre
- indiquer clairement sur la page de vote qu'il n'est plus possible de voter lorsque la date de fin
  d'un sondage avec durée est dépassée

## Objectifs pédagogiques

À l'issue de ce travail pratique, les étudiants devraient être capables de :

- concevoir et développer une application web frontend complète avec Vue.js, organisée de manière
  cohérente selon l'architecture retenue
- implémenter et consommer une API JSON versionnée
- exploiter des modèles relationnels déjà fournis pour construire des fonctionnalités cohérentes
- créer un frontend réactif pour gérer un tableau de bord, un éditeur et des vues de
  consultation
- interagir avec une API (`GET`, `POST`, `PUT`/`PATCH`, `DELETE`) et afficher
  dynamiquement les contenus

## Consignes générales

Vous développerez une application web en deux parties :

- Backend Laravel : responsable de l'exposition des endpoints JSON utilisés par le frontend
- Frontend Vue.js : responsable de l'affichage et des interactions autour des sondages, utilisable
  sur navigateur et mobile, avec une approche mobile first

L'architecture frontend est libre : il est possible de réaliser soit une seule application Vue.js
couvrant l'ensemble des usages, soit plusieurs applications Vue.js distinctes (par exemple une pour
le dashboard et une pour la consultation, le vote et la visualisation des résultats), à condition
que l'ensemble reste cohérent, maintenable et bien intégré au backend.

Le système d'authentification est déjà en place. Il est externe à l'application Vue.js, hors du
périmètre du travail, et doit être conservé en l'état. Le frontend demandé doit s'intégrer à ce
mécanisme existant, sans le redévelopper ni le modifier.

Un fichier `README_FRONT.md` est fourni à la racine du projet pour documenter l'intégration
frontend existante. Des exemples de fetch vers l'API, d'intégration de plusieurs applications Vue
et d'eager loading sont déjà disponibles dans le code fourni, notamment dans les vues et fichiers
liés aux sondages ainsi que dans certains contrôleurs API. Ces exemples peuvent servir de base de
travail et de référence.

Les modèles sont déjà fournis. Vous devez construire autour de ceux-ci les fonctionnalités utiles au
frontend. Les modèles existants permettent naturellement de représenter plusieurs choix pour un même sondage.
Par conséquent, lorsqu'un sondage est configuré en choix unique, l'unicité du vote doit être
garantie à la fois côté frontend et côté API.

Fonctionnalités attendues :

- afficher la liste des sondages de la personne connectée
- permettre la création, l'édition et la suppression d'un sondage depuis le frontend
- gérer les options d'un sondage
- gérer les paramètres du sondage (brouillon, lancement, choix simple ou multiple, résultats publics,
  dates ou durée)
- permettre au créateur d'obtenir facilement le lien de partage contenant le token
- afficher un sondage accessible via un token
- permettre à une personne authentifiée de voter via ce lien
- empêcher le vote après la date de fin d'un sondage avec durée, avec un affichage clair de cet état
- permettre l'accès anonyme aux résultats uniquement lorsqu'ils sont publics
- afficher les résultats via polling avec un aperçu graphique visualisant leur évolution
- garantir côté frontend et côté API l'unicité du vote pour les sondages à choix unique

Bonus possible :

- permettre de configurer si le vote peut être modifié après soumission
- permettre, si le sondage l'autorise, de modifier un vote déjà soumis

La structure exacte de l'interface est libre, à condition que l'application reste claire,
fonctionnelle et cohérente.

## Évaluation

Chaque partie du projet sera évaluée selon plusieurs catégories. L'évaluation portera sur :

- la qualité du frontend
- le bon fonctionnement des endpoints JSON nécessaires à ce frontend
- la capacité à expliquer, défendre et adapter son code à l'oral

Conditions particulières :

- toute triche avérée entraîne la note de `1` et aucune possibilité de remédiation ne sera proposée
- l'oral a un poids important dans l'évaluation afin de contrebalancer l'usage des IA et de vérifier
  la maîtrise réelle du travail rendu

Note maximale : `(nombre de points obtenus / nombre de points maximum) x 5 + 1`

## Critères frontend et endpoints JSON

Les informations ci-dessous sont à titre indicatif et peuvent être adaptées.

### Critères rendu

| # | Critère |
| --- | --- |
| 1 | Affichage d'un dashboard des sondages de la personne connectée
| 2 | Création, édition et suppression d'un sondage depuis le frontend
| 3 | Gestion des options du sondage (ajout, modification, suppression)
| 4 | Gestion des paramètres du sondage (brouillon, choix multiples, résultats publics, durée)
| 5 | Récupération simple du lien de partage contenant le token et affichage d'un sondage accessible via ce lien
| 6 | Soumission d'un vote valide depuis le frontend, avec unicité correctement garantie pour les sondages à choix unique
| 7 | Affichage conditionnel correct selon l'état du sondage, la date de fin et les droits d'accès, y compris l'accès anonyme aux résultats publics
| 8 | Consommation correcte des endpoints JSON par le frontend
| 9 | Gestion correcte des erreurs utilisateur côté frontend
| 10 | Interface lisible, claire, responsive et agréable à utiliser
| 11 | Affichage en temps réel, via polling, des résultats, avec aperçu graphique
| 12 | Le projet est fonctionnel de bout en bout
| 13 | Code lisible, structuré, `README` clair et utilisation correcte du contrôle de version
| 14 | Bon usage des composants Vue, des composables et d'une architecture cohérente du code
| 15 | Nommage, lisibilité et organisation générale du frontend (et routes API backend) soignés

Bonus possible : prise en charge du changement de vote lorsqu'un sondage l'autorise


## Critères présentation

| # | Critère |
| --- | --- |
| 1 | Les informations sont claires et bien présentées
| 2 | Les réponses aux questions sont pertinentes
| 3 | La capacité à modifier le code en direct selon une demande est satisfaisante
| 4 | La compréhension théorique de Vue.js, des échanges frontend/backend et de l'architecture fullstack est bonne
| 5 | La personne démontre qu'elle maîtrise réellement le code présenté, y compris si des outils d'IA ont été utilisés

## Contraintes techniques

- Backend Laravel >= 12.x
- Frontend Vue.js >= 3.4
- Base de données relationnelle (`SQLite`, `MySQL` ou `PostgreSQL`)
- Projet disponible sur GitHub
- Une documentation minimale (`README.md`) doit permettre de tester facilement l'application
- Les modèles et migrations sont fournis, mais les endpoints JSON nécessaires au frontend doivent
  être implémentés
- L'usage de l'IA est autorisé, mais le code rendu doit être compris, maîtrisé et défendable à l'oral
- Les critères liés à l'architecture, au découpage du code, au nommage et à la lisibilité auront une
  importance particulière
- L'usage d'outils d'IA ne dispense pas d'un regard critique : un code trop verbeux, mal structuré ou
  peu cohérent sera pénalisé

## Conseils

- Ne cherchez pas à faire complexe : commencez simple, itérez ensuite.
- Travaillez de manière incrémentale et validez chaque étape.
- Testez tôt et souvent.
- Une fonctionnalité simple mais fiable vaut mieux qu'une fonctionnalité ambitieuse inachevée.
- Structurez clairement les données échangées entre votre frontend et votre API JSON.

## Livrables et rendu

Vous devez fournir :

- l'URL du dépôt GitHub
- un fichier `README.md` clair pour expliquer l'installation et les choix techniques
- Il est possible de mettre à jour le dépôt entre le jour du rendu et l'examen
- Seul le code présent avant l'échéance sera évalué pour le rendu
- Le code ajouté ou modifié après l'échéance ne sera pas évalué pour la note de rendu, mais pourra
  éventuellement aider lors de la présentation orale

Rendu final : au plus tard le dimanche 17 mai 2026 à 23:59:59 UTC (date du commit).

La présentation orale aura lieu lors de la période des examens et sera probablement d'une durée de 20 minutes par étudiant.
