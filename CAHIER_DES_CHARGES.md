# Cahier des charges — Salon de Massage

**Projet** : Application web de gestion et de réservation pour un réseau de salons de massage avec des chats trop mignons
**Framework** : Symfony 8.1  

---

## 1. Contexte et objectifs

### 1.1 Présentation

**Salon de Massage** est une plateforme web permettant à des clients de rechercher, réserver et suivre des prestations de massage réalisées par des **chats masseurs** dans différents salons physiques ou à distance (live chat).

Le projet s'inscrit dans une thématique ludique : chaque masseur est un chat (espèce, couleur, spécialité) affecté à un ou plusieurs salons.

### 1.2 Objectifs fonctionnels

| Objectif | Description |
|----------|-------------|
| **Découverte** | Rechercher une prestation par ville, type de massage ou mot-clé |
| **Réservation** | Réserver un créneau en salon ou démarrer un live chat à distance |
| **Suivi client** | Consulter ses réservations à venir et passées |
| **Gestion salon** | Permettre aux managers de gérer leur équipe et le catalogue |
| **Espace masseur** | Permettre aux chats masseurs de gérer profil, prestations et planning |
| **Administration** | Gérer les utilisateurs, les rôles et les salons à l'échelle de la plateforme |
| **Communication** | Envoyer des e-mails transactionnels (inscription, confirmation de réservation) |
| **API** | Exposer des endpoints REST JSON pour les prestations et la météo |
| **Assistance** | Proposer un chatbot météo orientant l'utilisateur selon le temps qu'il fait |

### 1.3 Périmètre hors scope (état actuel)

Les tables suivantes existent en base mais **ne sont pas exploitées** par l'application :

- `subscribe`, `giftcard`, `extra`, `review`, `reservation_extra`

Les commandes console suivantes sont **référencées** (Makefile / worker Docker) mais **non implémentées** :

- `app:reservation:expire-pending`
- `app:reservation:complete-past`
- `app:ical:sync`

---

## 2. Acteurs et rôles

### 2.1 Typologie des utilisateurs

| Acteur | Rôle Symfony | Description |
|--------|--------------|-------------|
| **Visiteur** | — | Consulte l'accueil, peut s'inscrire ou se connecter |
| **Client** | `ROLE_USER` | Recherche, réserve, consulte ses réservations, utilise le live chat |
| **Manager** | `ROLE_MANAGER` | Gère un salon : prestations globales, recrutement de chats masseurs |
| **Chat masseur** | `ROLE_CAT` | Gère son profil, ses prestations et son planning |
| **Administrateur global** | `ROLE_MANAGER` + voter `GLOBAL_ADMIN` | Gère utilisateurs, rôles, salons et managers globaux |

> **Note** : le rôle `ROLE_ADMIN` existe dans l'enum `UserRole` mais n'est pas utilisé dans la configuration de sécurité ni dans les formulaires d'administration.

### 2.2 Hiérarchie des rôles

```
ROLE_MANAGER → hérite de ROLE_USER
ROLE_CAT     → hérite de ROLE_USER
```

### 2.3 Matrice des accès

| Fonctionnalité | Visiteur | Client | Manager | Chat | Admin global |
|----------------|:--------:|:------:|:-------:|:----:|:------------:|
| Accueil | ✓ | ✓ | ✓ | ✓ | ✓ |
| Inscription / Connexion | ✓ | — | — | — | — |
| Recherche prestations | — | ✓ | — | — | — |
| Réservation | — | ✓ | — | — | — |
| Mes réservations | — | ✓ | — | — | — |
| Live chat | — | ✓ | — | — | — |
| Espace manager | — | — | ✓ | — | ✓* |
| Espace chat masseur | — | — | — | ✓ | — |
| Administration | — | — | — | — | ✓ |
| API météo (`/api/v1/weather`) | ✓ | ✓ | ✓ | ✓ | ✓ |
| API prestations (`/api/v1/*`) | — | ✓ | ✓ | ✓ | ✓ |

\* L'admin global accède aussi à l'espace manager pour les services globaux.

---

## 3. Cas d'utilisation (Use Cases)

Chaque cas d'utilisation est identifié par un code **UC-XX**. Les scénarios alternatifs couvrent les erreurs les plus fréquentes.

### UC-01 — S'inscrire en tant que client

| Élément | Description |
|---------|-------------|
| **Acteur** | Visiteur |
| **Préconditions** | Aucune |
| **Scénario nominal** | 1. Le visiteur accède à `/register`. 2. Il saisit prénom, nom, e-mail, téléphone et mot de passe. 3. Il valide le formulaire. 4. Le système crée le compte avec le rôle `ROLE_USER`. 5. Un e-mail de bienvenue est envoyé (async). 6. Le visiteur est redirigé vers la page de connexion. |
| **Scénarios alternatifs** | **A1** — E-mail déjà utilisé : message d'erreur, formulaire réaffiché. **A2** — Mot de passe trop court : message d'erreur. **A3** — Jeton CSRF invalide : message d'erreur. |
| **Postconditions** | Compte client créé en base, mot de passe hashé. |

### UC-02 — Se connecter

| Élément | Description |
|---------|-------------|
| **Acteur** | Visiteur, Client, Manager, Chat masseur, Admin global |
| **Préconditions** | Compte existant |
| **Scénario nominal** | 1. L'utilisateur accède à `/login`. 2. Il saisit e-mail et mot de passe. 3. Le système authentifie (e-mail insensible à la casse). 4. Redirection vers l'accueil. |
| **Scénarios alternatifs** | **A1** — Identifiants incorrects : message « Invalid credentials ». **A2** — Jeton CSRF invalide : accès refusé. |
| **Postconditions** | Session utilisateur ouverte. |

### UC-03 — Rechercher une prestation

| Élément | Description |
|---------|-------------|
| **Acteur** | Client |
| **Préconditions** | Utilisateur connecté avec `ROLE_USER` |
| **Scénario nominal** | 1. Le client accède à `/recherche`. 2. Il applique des filtres (salon, prestation, mot-clé). 3. Le système affiche les offres croisant prestation + salon + chat masseur. 4. Les offres live chat à distance sont incluses si pertinent. |
| **Scénarios alternatifs** | **A1** — Aucun résultat : message invitant à élargir les critères. |
| **Postconditions** | Liste d'offres affichée. |

### UC-04 — Réserver une prestation en salon

| Élément | Description |
|---------|-------------|
| **Acteur** | Client |
| **Préconditions** | Connecté, offre disponible |
| **Scénario nominal** | 1. Le client clique « Réserver » sur une offre. 2. Il choisit un créneau futur. 3. Il valide le récapitulatif. 4. Le système crée la réservation (`confirmed`) avec snapshots. 5. E-mail de confirmation envoyé (async). 6. Redirection vers « Mes réservations ». |
| **Scénarios alternatifs** | **A1** — Créneau passé : message d'erreur. **A2** — Offre plus disponible : redirection recherche. **A3** — CSRF invalide : accès refusé. |
| **Postconditions** | Réservation enregistrée. |

### UC-05 — Réserver un live chat à distance

| Élément | Description |
|---------|-------------|
| **Acteur** | Client |
| **Préconditions** | Connecté, offre live chat disponible |
| **Scénario nominal** | 1. Le client lance une offre live chat. 2. Il confirme sur la page dédiée. 3. Réservation créée immédiatement. 4. Redirection vers l'interface de chat. |
| **Scénarios alternatifs** | **A1** — Prestation ou chat introuvable : message d'erreur. |
| **Postconditions** | Réservation à distance créée. |

### UC-06 — Consulter ses réservations

| Élément | Description |
|---------|-------------|
| **Acteur** | Client |
| **Préconditions** | Connecté |
| **Scénario nominal** | 1. Le client accède à `/mes-reservations`. 2. Le système affiche les réservations à venir et passées. |
| **Postconditions** | Aucune modification. |

### UC-07 — Échanger via le live chat

| Élément | Description |
|---------|-------------|
| **Acteur** | Client |
| **Préconditions** | Réservation live chat existante, propriétaire de la réservation |
| **Scénario nominal** | 1. Le client ouvre `/live-chat/{id}`. 2. Il envoie un message. 3. Le système enregistre le message et génère une réponse automatique du chat. 4. Les messages s'affichent sans rechargement (AJAX). |
| **Scénarios alternatifs** | **A1** — Réservation d'un autre client : accès refusé. **A2** — Message vide : ignoré. |
| **Postconditions** | Messages persistés en base. |

### UC-08 — Créer son profil masseur chat

| Élément | Description |
|---------|-------------|
| **Acteur** | Chat masseur |
| **Préconditions** | Connecté avec `ROLE_CAT`, profil Cat absent |
| **Scénario nominal** | 1. Le chat accède à `/espace-cat`. 2. Il remplit espèce, couleur, spécialité. 3. Le profil est enregistré. |
| **Postconditions** | Entité `Cat` créée et liée au compte. |

### UC-09 — Choisir ses prestations

| Élément | Description |
|---------|-------------|
| **Acteur** | Chat masseur |
| **Préconditions** | Profil Cat existant, rattaché à au moins un salon |
| **Scénario nominal** | 1. Le chat coche les prestations du catalogue global. 2. Il enregistre. 3. Les offres de recherche incluent ces combinaisons salon/prestation/chat. |
| **Postconditions** | Relations `service_cat` mises à jour. |

### UC-10 — Consulter son planning

| Élément | Description |
|---------|-------------|
| **Acteur** | Chat masseur |
| **Préconditions** | Connecté avec `ROLE_CAT` |
| **Scénario nominal** | 1. Le chat accède au planning (vue semaine ou mois). 2. Le système affiche les réservations confirmées le concernant. 3. Navigation temporelle possible. |
| **Postconditions** | Aucune modification. |

### UC-11 — Gérer un salon (manager)

| Élément | Description |
|---------|-------------|
| **Acteur** | Manager de salon |
| **Préconditions** | `ROLE_MANAGER`, salon assigné |
| **Scénario nominal** | 1. Le manager accède à `/manager`. 2. Il crée une prestation globale et/ou recrute un chat masseur inscrit. 3. Les données sont persistées. |
| **Scénarios alternatifs** | **A1** — Aucun salon assigné : message d'attente. **A2** — Aucun chat disponible au recrutement : message informatif. |
| **Postconditions** | Catalogue ou effectif du salon mis à jour. |

### UC-12 — Administrer la plateforme

| Élément | Description |
|---------|-------------|
| **Acteur** | Administrateur global (`GLOBAL_ADMIN`) |
| **Préconditions** | Manager rattaché à la localisation globale |
| **Scénario nominal** | 1. L'admin accède à `/admin`. 2. Il modifie le rôle d'un utilisateur et/ou crée un salon avec manager. 3. Les changements sont enregistrés. |
| **Scénarios alternatifs** | **A1** — Accès sans droits global : HTTP 403. |
| **Postconditions** | Rôles et salons mis à jour. |

### UC-13 — Consulter l'API prestations

| Élément | Description |
|---------|-------------|
| **Acteur** | Client (authentifié) |
| **Préconditions** | Session valide |
| **Scénario nominal** | 1. Le client appelle `GET /api/v1/prestations` ou `POST /api/v1/prestations/search`. 2. Le système retourne du JSON via le Serializer (groupes de normalisation). |
| **Scénarios alternatifs** | **A1** — Non authentifié : redirection login / 401. |
| **Postconditions** | Aucune modification. |

### UC-14 — Obtenir un conseil météo

| Élément | Description |
|---------|-------------|
| **Acteur** | Tout visiteur ou utilisateur |
| **Préconditions** | Aucune (endpoint public) |
| **Scénario nominal** | 1. L'utilisateur survole le chatbot « Météo ». 2. Le front appelle `GET /api/v1/weather` (géolocalisation optionnelle). 3. Le chaton affiche météo et recommandation (salon ou prestation à domicile). |
| **Scénarios alternatifs** | **A1** — API indisponible : message d'erreur dans le widget. **A2** — Clé API absente : mode démo. |
| **Postconditions** | Aucune modification. |

### UC-15 — Recevoir un e-mail transactionnel

| Élément | Description |
|---------|-------------|
| **Acteur** | Système (déclenché par inscription ou réservation) |
| **Préconditions** | Événement métier dispatché, worker Messenger actif |
| **Scénario nominal** | 1. `UserRegisteredEvent` ou `ReservationConfirmedEvent` est émis. 2. `TransactionalEmailSubscriber` envoie via Notifier. 3. Message routé en async. 4. Worker consomme et Mailer envoie l'e-mail. |
| **Scénarios alternatifs** | **A1** — Worker arrêté : message en file d'attente. **A2** — Échec d'envoi : file `failed`. |
| **Postconditions** | E-mail délivré (ou en attente). |

---

## 4. Exigences fonctionnelles

### 4.1 Authentification et inscription

**Inscription (`/register`)**

- Champs : prénom, nom, e-mail, téléphone (optionnel), mot de passe
- Validation : e-mail valide, mot de passe ≥ 6 caractères, unicité de l'e-mail
- Normalisation de l'e-mail en minuscules
- Attribution automatique du rôle `ROLE_USER`
- Hash sécurisé du mot de passe (Symfony PasswordHasher)
- Protection CSRF
- Déclenchement d'un e-mail de bienvenue (asynchrone)

**Connexion (`/login`)**

- Identifiant : adresse e-mail (insensible à la casse)
- Mot de passe + jeton CSRF
- Redirection vers l'accueil après authentification réussie

**Déconnexion (`/logout`)**

- Retour vers la page de connexion

### 4.2 Accueil (`/`)

- Message de bienvenue personnalisé si connecté
- Affichage des 3 prochaines réservations pour les clients
- Liens rapides selon le rôle (recherche, espace manager, administration, espace chat)

### 4.3 Recherche de prestations (`/recherche`)

**Filtres disponibles**

- Centre / salon (liste des salons physiques)
- Type de prestation (catalogue global)
- Recherche textuelle (ville, adresse, spécialité du chat, etc.)

**Résultats**

- Grille d'offres combinant : **prestation + salon + chat masseur**
- Prestation live chat à distance proposée par tous les chats (sans salon physique)
- Bouton « Réserver » ou « Démarrer le live chat » selon le type d'offre

### 4.4 Réservation en salon

**Étape 1 — Choix du créneau** (`/reservation/reserver/{serviceId}/{locationId}/{catId}`)

- Récapitulatif de l'offre (prestation, salon, masseur)
- Formulaire de sélection date/heure (créneau futur obligatoire)

**Étape 2 — Confirmation** (`/reservation/valider` POST)

- Récapitulatif complet avant validation définitive
- Protection CSRF
- Création de la réservation en base avec :
  - Snapshots textuels (libellé prestation, libellé chat, prix, durée)
  - Statut `confirmed`
- Envoi d'un e-mail de confirmation (asynchrone)
- Redirection vers « Mes réservations »

### 4.5 Réservation live chat à distance

**Page de confirmation** (`/reservation/live-chat/{serviceId}/{catId}`)

- Prestation spéciale « Live chat avec masseur chat »
- Lieu virtuel « À distance »
- Pas de créneau horaire : démarrage immédiat

**Validation** (`/reservation/live-chat/valider` POST)

- Création de la réservation à l'instant T
- Redirection vers l'interface de live chat

### 4.6 Mes réservations (`/mes-reservations`)

- Section **prochaines réservations** (triées par date croissante)
- Section **réservations passées**
- Carte détaillée : date, prestation, salon, masseur, prix, durée
- Lien « Ouvrir le live chat » pour les réservations à distance

### 4.7 Live chat (`/live-chat/{reservationId}`)

- Interface de messagerie en temps réel (AJAX)
- Messages du client et réponses automatiques du chat masseur
- Réponses générées aléatoirement (simulation « frappe au clavier »)
- Accès réservé au client propriétaire de la réservation
- Historique des messages persisté en base (`live_chat_message`)

### 4.8 Espace manager (`/manager`)

**Sans salon assigné**

- Message invitant à contacter l'administrateur

**Manager de salon physique**

- Création de prestations globales (titre, description, durée, prix)
- Recrutement de chats masseurs inscrits sur la plateforme
- Liste des masseurs du salon
- Catalogue des prestations existantes

**Manager global (localisation globale)**

- Accès à l'interface d'administration
- Gestion des services globaux de la plateforme

### 4.9 Espace chat masseur (`/espace-cat`)

**Création de profil** (si absent)

- Espèce, couleur, spécialité de massage

**Tableau de bord**

- Profil masseur
- Salons de rattachement
- Sélection des prestations proposées (cases à cocher)
- Lien vers le planning

**Planning** (`/espace-cat/planning`)

- Vue **semaine** ou **mois**
- Affichage des réservations confirmées
- Navigation temporelle (précédent / suivant)

### 4.10 Administration globale (`/admin`)

**Accès** : voter `GLOBAL_ADMIN` (manager rattaché à la localisation globale)

**Gestion des utilisateurs** (`/admin/users`)

- Liste de tous les utilisateurs inscrits
- Modification du rôle (client, manager, chat masseur)
- Badges visuels par rôle

**Gestion des salons** (`/admin/locations`)

- Affichage de la localisation globale et de ses managers
- Ajout de managers globaux
- Création de salons par ville (ville, adresse, pays, manager responsable)
- Liste des salons existants avec masseurs affectés

### 4.11 API REST v1

#### `GET /api/v1/weather` — Public

Retourne la météo en temps réel et le conseil du chaton assistant.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `lat` | float | Latitude (optionnel) |
| `lon` | float | Longitude (optionnel) |
| `city` | string | Ville (optionnel, défaut : `Paris,FR`) |

**Réponse JSON** : ville, température, description, icône, condition, `isGoodWeather`, `kittenMessage`, `action` (label + url).

#### `GET /api/v1/prestations` — Authentifié

Liste du catalogue des prestations globales (Serializer, groupe `api:read`).

#### `GET /api/v1/prestations/{id}` — Authentifié

Détail d'une prestation (groupes `api:read` + `api:read:detail` : description, `isGlobal`).

#### `POST /api/v1/prestations/search` — Authentifié

Recherche de prestations via JSON.

**Corps de requête** :
```json
{
  "locationId": 2,
  "serviceId": null,
  "query": "relaxant"
}
```

**Réponse** : tableau d'offres (`service`, `location`, `cat`) normalisées via le Serializer Symfony.

### 4.12 E-mails transactionnels

| Événement | Déclencheur | Template | Contenu |
|-----------|-------------|----------|---------|
| Inscription | `UserRegisteredEvent` | `email/user_registered.html.twig` | Bienvenue, identifiant, lien de connexion |
| Réservation confirmée | `ReservationConfirmedEvent` | `email/reservation_confirmed.html.twig` | Récapitulatif complet + référence |

**Contraintes techniques**

- Envoi via **Symfony Notifier** + **Mailer**
- Routage **asynchrone** via **Symfony Messenger** (transport Doctrine)
- Templates HTML responsive (Twig Inky + CSS Inliner)
- Capture des e-mails en développement via **Mailpit**

### 4.13 Chatbot météo (assistant « Mimi »)

**Présence** : widget flottant sur toutes les pages (inclus dans le layout de base)

**Comportement**

- Au survol ou au clic sur la bulle « Météo », un panneau s'ouvre avec un chaton animé
- Appel à l'API `/api/v1/weather` (géolocalisation navigateur si autorisée)
- Affichage : ville, température, icône OpenWeather, message personnalisé

**Logique de recommandation**

| Conditions météo | Message du chaton | Action proposée |
|------------------|-------------------|-----------------|
| Beau temps (ciel dégagé, peu nuageux) | Invitation à venir au salon | Lien vers `/recherche` |
| Mauvais temps (pluie, neige, brouillard, orage…) | Invitation à rester chez soi | Lien vers prestation à distance (`/recherche?q=live chat`) |

**Source de données** : API OpenWeatherMap 2.5, cache 10 minutes, mode démo si clé API absente.

---

## 5. Modèle de données

### 5.1 Entités principales

#### User (`users`)

| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant |
| name | text | Nom |
| firstname | text | Prénom |
| email | varchar(255) | E-mail unique (connexion) |
| password | text | Mot de passe hashé |
| birthdate | int | Date de naissance (optionnel) |
| phone | varchar(20) | Téléphone (optionnel) |
| role | varchar(50) | Rôle Symfony |
| subscribe_id | int | Abonnement (non exploité) |

#### Cat (`cat`)

| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant |
| specie | text | Espèce (ex. Siamois) |
| color | text | Couleur du pelage |
| speciality | text | Spécialité de massage |
| user_id | int | Compte utilisateur lié (1:1) |

#### Location (`location`)

| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant |
| address | text | Adresse |
| country | text | Pays |
| city | text | Ville |
| is_global | boolean | Localisation globale (plateforme) |
| is_remote | boolean | Lieu virtuel « À distance » |

#### Service (`service`)

| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant |
| title | text | Nom de la prestation |
| description | text | Description détaillée |
| duration | varchar | Durée (ex. « 60 min ») |
| price | int | Prix |
| is_global | boolean | Disponible dans tous les salons |
| is_remote_live_chat | boolean | Prestation live chat à distance |

#### Reservation (`reservation`)

| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant |
| user_id | int | Client |
| service_id | int | Prestation (entité) |
| location_id | int | Salon |
| service | text | Libellé snapshot |
| cat | text | Libellé chat snapshot |
| date | datetime | Date du rendez-vous |
| hour | varchar | Heure (H:i) |
| reservation_date | datetime | Date/heure complète |
| duration | varchar | Durée snapshot |
| price | int | Prix snapshot |
| status | varchar(20) | Statut (`confirmed`) |

#### Manager (`manager`)

| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant |
| user_id | int | Compte utilisateur (1:1) |
| location_id | int | Salon géré |
| is_admin | boolean | Manager global (accès admin) |

#### LiveChatMessage (`live_chat_message`)

| Champ | Type | Description |
|-------|------|-------------|
| id | int | Identifiant |
| reservation_id | int | Réservation liée |
| sender | varchar | `user` ou `cat` |
| content | text | Contenu du message |
| created_at | datetime | Horodatage |

### 5.2 Relations

```
User 1──1 Cat
User 1──1 Manager
Manager N──1 Location
Cat N──M Location       (cat_location)
Cat N──M Service        (service_cat)
Reservation N──1 User / Service / Location
Reservation N──M Cat  (reservation_cat)
LiveChatMessage N──1 Reservation
```

### 5.3 Données de référence (seed / migrations)

- **Localisation globale** : ville « Global », gérée par l'administrateur
- **Lieu virtuel** : ville « À distance », pour le live chat
- **Prestation live chat** : unique, proposée par tous les chats sans rattachement salon

---

## 6. Exigences techniques

### 6.1 Stack logicielle

| Composant | Technologie |
|-----------|-------------|
| Langage | PHP 8.4 |
| Framework | Symfony 8.1 |
| ORM | Doctrine 3.6 |
| Base de données | PostgreSQL 16 |
| Templates | Twig 3 |
| CSS | Feuille de style globale (`public/css/app.css`) |
| JavaScript | Vanilla JS (live chat, chatbot météo) |
| Conteneurisation | Docker Compose |
| E-mail (dev) | Mailpit |
| File d'attente | Symfony Messenger (Doctrine transport) |
| API externe | OpenWeatherMap |
| Tests | PHPUnit 11 |

### 6.2 Dépendances Symfony principales

- `symfony/security-bundle` — Authentification et autorisation
- `symfony/form` — Formulaires
- `symfony/serializer` — API JSON (groupes de normalisation)
- `symfony/mailer` + `symfony/notifier` — E-mails transactionnels
- `symfony/messenger` + `symfony/doctrine-messenger` — Traitement asynchrone
- `symfony/http-client` — Appels API OpenWeather
- `twig/inky-extra` + `twig/cssinliner-extra` — E-mails HTML responsive

### 6.3 Architecture applicative

```
src/
├── Command/           # Commandes console (admin, reset password)
├── Controller/        # Contrôleurs web
│   └── Api/V1/        # Contrôleurs API REST
├── Dto/               # Objets de transfert (API, offres)
├── Entity/            # Entités Doctrine
├── Enum/              # Rôles, statuts
├── Event/             # Événements métier
├── EventSubscriber/   # E-mails transactionnels
├── Form/              # Types de formulaires Symfony
├── Notification/      # Notifications Notifier (e-mails)
├── Repository/        # Requêtes Doctrine
├── Security/          # UserProvider, Voters
├── Serializer/        # Groupes de sérialisation API
└── Service/           # Logique métier
```

### 6.4 Sécurité

- Mots de passe hashés via `PasswordHasher` (algorithme auto)
- Protection CSRF sur connexion et formulaires sensibles
- Contrôle d'accès par routes (`access_control`)
- Voter personnalisé `GlobalAdminVoter` pour l'administration
- API prestations protégée par session (`ROLE_USER`)
- API météo publique (widget accessible à tous)

### 6.5 Performance et cache

- Cache applicatif Symfony (`var/cache`)
- Cache météo OpenWeather : 10 minutes
- Chargement paresseux du firewall (`lazy: true`)

---

## 7. Infrastructure Docker

### 7.1 Services

| Service | Image | Port hôte | Rôle |
|---------|-------|-----------|------|
| `database` | postgres:16-alpine | 5439 | Base PostgreSQL |
| `adminer` | adminer:latest | 8088 | Interface d'administration BDD |
| `php` | Dockerfile.dev | 8089 | Application Symfony |
| `mailer` | axllent/mailpit | 1025 / 8025 | SMTP + interface e-mails |
| `messenger-worker` | Dockerfile.dev | — | Consommation file async |

### 7.2 Démarrage automatique (entrypoint)

1. Copie `.env.example` → `.env` si absent
2. Génération `APP_SECRET`
3. `composer install`
4. Warm-up du cache
5. Exécution des migrations Doctrine
6. Création du compte administrateur (`app:create-admin-user`)
7. Démarrage du serveur PHP sur le port 8000

### 7.3 URLs de développement

| Service | URL |
|---------|-----|
| Application | http://localhost:8089 |
| Adminer (BDD) | http://localhost:8088 |
| Mailpit (e-mails) | http://localhost:8025 |

### 7.4 Commandes Makefile

| Commande | Description |
|----------|-------------|
| `make install` | Build + démarrage complet |
| `make up` | Démarrer les conteneurs |
| `make down` | Arrêter les conteneurs |
| `make migrate` | Exécuter les migrations |
| `make fixtures` | Charger les fixtures de démo (groupe `demo`, purge la BDD) |
| `make init` | Migrations + fixtures |
| `make test` | Lancer PHPUnit |
| `make sh` | Shell dans le conteneur PHP |
| `make cache` | Vider le cache Symfony |
| `make logs` | Suivre les logs PHP |

---

## 8. Variables d'environnement

| Variable | Description | Exemple |
|----------|-------------|---------|
| `APP_ENV` | Environnement Symfony | `dev` |
| `APP_SECRET` | Clé secrète Symfony | *(généré automatiquement)* |
| `DATABASE_URL` | Connexion PostgreSQL | `postgresql://app:...@database:5432/app` |
| `ADMIN_EMAIL` | E-mail admin initial | `admin@salon.local` |
| `ADMIN_PASSWORD` | Mot de passe admin initial | *(défini localement)* |
| `MAILER_DSN` | Transport e-mail | `smtp://mailer:1025` |
| `MAILER_FROM` | Expéditeur des e-mails | `Salon de Massage <noreply@salon.local>` |
| `MESSENGER_TRANSPORT_DSN` | Transport async | `doctrine://default` |
| `OPENWEATHER_API_KEY` | Clé API OpenWeather | *(clé personnelle)* |
| `OPENWEATHER_DEFAULT_CITY` | Ville par défaut météo | `Paris,FR` |

> Les secrets (`APP_SECRET`, clés API, mots de passe) doivent être stockés dans `.env.local` (non versionné).

---

## 9. Interface utilisateur

### 9.1 Charte graphique

- Palette : fond gris clair, navigation sombre, teal primaire, violet pour le live chat
- Typographie : system-ui / Segoe UI
- Composants : cartes avec ombre, boutons arrondis, badges, tableaux admin
- Mise en page responsive (mobile-first sur les grilles)

### 9.2 Pages principales

| Zone | Templates |
|------|-----------|
| Layout global | `base.html.twig` |
| Accueil | `home/index.html.twig` |
| Authentification | `security/login.html.twig`, `register.html.twig` |
| Recherche | `search/index.html.twig` |
| Réservation | `reservation/book.html.twig`, `confirm.html.twig`, `book_remote.html.twig` |
| Mes réservations | `reservation/user_list.html.twig`, `_card.html.twig` |
| Live chat | `live_chat/index.html.twig` |
| Espace chat | `cat/index.html.twig`, `planning.html.twig` |
| Espace manager | `manager/index.html.twig` |
| Administration | `admin/index.html.twig`, `users.html.twig`, `locations.html.twig` |
| E-mails | `email/base.html.twig`, `user_registered.html.twig`, `reservation_confirmed.html.twig` |
| Chatbot météo | `_partials/weather_chatbot.html.twig` |

### 9.3 Navigation

Barre de navigation conditionnelle selon le rôle connecté, avec pied de page et messages flash centralisés.

---

## 10. Scénarios utilisateur (parcours types)

### 10.1 Parcours client — Réservation en salon

1. S'inscrire sur `/register`
2. Se connecter
3. Rechercher une prestation sur `/recherche`
4. Cliquer « Réserver » sur une offre
5. Choisir un créneau futur
6. Valider la réservation
7. Recevoir l'e-mail de confirmation (Mailpit en dev)
8. Consulter la réservation sur `/mes-reservations`

### 10.2 Parcours client — Live chat à distance

1. Se connecter
2. Rechercher « live chat » ou filtrer les offres à distance
3. Cliquer « Démarrer le live chat »
4. Confirmer la réservation immédiate
5. Échanger des messages avec le chat masseur

### 10.3 Parcours chat masseur

1. Recevoir le rôle `ROLE_CAT` depuis l'administration
2. Se connecter → `/espace-cat`
3. Créer son profil (espèce, couleur, spécialité)
4. Attendre d'être recruté par un manager dans un salon
5. Cocher les prestations proposées
6. Consulter son planning

### 10.4 Parcours manager

1. Recevoir le rôle `ROLE_MANAGER` et être assigné à un salon
2. Se connecter → `/manager`
3. Créer des prestations globales
4. Recruter des chats masseurs disponibles

### 10.5 Parcours administrateur global

1. Compte créé automatiquement au premier lancement Docker
2. Se connecter → `/admin`
3. Gérer les rôles des utilisateurs
4. Créer des salons par ville avec un manager responsable

---

## 11. Migrations de base de données

| Version | Description |
|---------|-------------|
| `20260630120000` | Schéma initial (users, cat, service, location, manager, reservation, tables M2M) |
| `20260702120000` | Relations Manager ↔ User, flags `is_global` |
| `20260702130000` | Inversion Manager → Location |
| `20260703120000` | Table `cat_location`, liaison Cat ↔ User |
| `20260704120000` | Colonne `reservation.status` |
| `20260705120000` | Live chat : lieu virtuel, prestation à distance, `live_chat_message` |
| `20260705140000` | Table `messenger_messages` (file async) |
| `20260706120000` | Normalisation e-mails + index unique sur `users.email` |

---

## 12. Tests et validation

### 12.1 Tests automatisés

- Framework : PHPUnit 11
- Tests unitaires existants : services (recherche, réservation, live chat, clavier chat)
- Commande : `make test` ou `docker compose exec php php bin/phpunit`

### 12.2 Tests manuels recommandés

| Fonctionnalité | Procédure |
|----------------|-----------|
| Inscription / Connexion | Créer un compte, se connecter, vérifier la redirection |
| Recherche | Filtrer par ville, service, mot-clé |
| Réservation salon | Réserver un créneau futur, vérifier l'e-mail Mailpit |
| Live chat | Envoyer un message, vérifier la réponse du chat |
| API prestations | `GET /api/v1/prestations` (connecté) |
| API météo | `GET /api/v1/weather` + widget chatbot |
| E-mails async | `make worker` puis déclencher une inscription |
| Administration | Changer un rôle, créer un salon |

---

## 13. Livrables

| Livrable | Emplacement |
|----------|-------------|
| Code source Symfony | `src/`, `config/`, `templates/`, `public/` |
| Configuration Docker | `compose.yaml`, `Dockerfile.dev`, `docker/entrypoint.sh` |
| Migrations BDD | `migrations/` |
| Tests | `tests/` |
| Documentation | `CAHIER_DES_CHARGES.md`, `README.md` |
| Schéma BDD | `bdd_schema.pdf` |
| Fixtures de démonstration | `src/DataFixtures/AppFixtures.php` (groupe `demo`) |
| Makefile | `Makefile` |

---

## 14. Évolutions possibles (hors périmètre actuel)

- Exploitation des tables `review`, `giftcard`, `subscribe`, `extra`
- Commandes de maintenance (expiration réservations, sync iCal)
- Authentification API par token JWT
- Paiement en ligne
- Notifications push / SMS via Notifier
- Interface mobile dédiée
- Activation de RabbitMQ ou Redis (services commentés dans Docker)

---

*Document généré à partir de l'état du projet Symfony « Salon de Massage » — Juillet 2026.*
