# Salon de Massage — Application Symfony

Application web de réservation de prestations de massage dispensées par des **chats masseurs**, en salon ou à distance (live chat).

**Stack :** PHP 8.4, Symfony 8.1, PostgreSQL 16, Docker Compose, Twig, Messenger, Mailer, OpenWeather.

Documentation fonctionnelle complète : [`CAHIER_DES_CHARGES.md`](CAHIER_DES_CHARGES.md).

---

## Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (ou Docker Engine + Compose)
- Ports disponibles par défaut : **8091** (app), **8090** (Adminer), **8026** (Mailpit), **5440** (PostgreSQL)
- En cas de conflit, définir dans un fichier `.env` à la racine : `APP_PORT`, `ADMINER_PORT`, `POSTGRES_HOST_PORT`, `MAILPIT_UI_PORT`

---

## Installation rapide

```bash
# 1. Cloner le dépôt et se placer à la racine du projet
cd symfony-final-project

# 2. Copier les variables d'environnement (optionnel pour la météo)
cp .env .env.local
# Éditer .env.local : OPENWEATHER_API_KEY=your_key

# 3. Premier lancement (build + démarrage des conteneurs)
make install
# ou : docker compose up -d --build

# 4. Charger les données de démonstration (purge la base)
make fixtures
# ou : make init   # migrations + fixtures
```

L'entrypoint Docker exécute automatiquement les **migrations** et crée un compte admin minimal au premier démarrage. Les **fixtures** doivent être chargées manuellement (`make fixtures`) pour obtenir le jeu de données complet.

---

## URLs utiles

| Service | URL |
|---------|-----|
| Application | http://localhost:8091 |
| Adminer (BDD) | http://localhost:8090 — serveur `database`, user `app`, mot de passe `my-super-secret-password`, base `app` |
| Mailpit (e-mails) | http://localhost:8026 |

---

## Comptes de démonstration

Chargés par `make fixtures` (`src/DataFixtures/AppFixtures.php`, groupe `demo`).

| E-mail | Mot de passe | Rôle |
|--------|--------------|------|
| `admin@salon.local` | `Admin123!` | Administrateur global |
| `client@demo.local` | `Client123!` | Client |
| `client.extra@demo.local` | `Client123!` | Client (données Faker) |
| `manager.paris@demo.local` | `Manager123!` | Manager — salon Paris |
| `manager.lyon@demo.local` | `Manager123!` | Manager — salon Lyon |
| `chat.siam@demo.local` | `Cat123!` | Chat masseur (Siamois) |
| `chat.persan@demo.local` | `Cat123!` | Chat masseur (Persan) |

**Données incluses :** salons Paris, Lyon, Marseille (+ plateforme globale et mode à distance), 4 prestations, 2 chats masseurs, 4 réservations dont une session live chat avec messages.

---

## Commandes Makefile

```bash
make help          # Liste des commandes
make up            # Démarrer les conteneurs
make down          # Arrêter les conteneurs
make sh            # Shell dans le conteneur PHP
make migrate       # Exécuter les migrations Doctrine
make fixtures      # Charger les fixtures (groupe demo, purge la BDD)
make init          # migrate + fixtures
make worker        # Consommer la file Messenger (e-mails async)
make test          # Lancer PHPUnit
make test-init     # Préparer la base de test app_test
make logs          # Logs du conteneur PHP
```

Sans `make` :

```bash
docker compose exec php php bin/console doctrine:fixtures:load --group=demo --no-interaction
docker compose exec php php bin/console messenger:consume async -vv
```

---

## Variables d'environnement

Fichiers : `.env`, `.env.dev`, `.env.local` (non versionné).

| Variable | Description |
|----------|-------------|
| `DATABASE_URL` | Connexion PostgreSQL (surchargée dans `compose.yaml` pour Docker) |
| `MAILER_DSN` | SMTP — par défaut Mailpit (`smtp://mailer:1025`) |
| `MESSENGER_TRANSPORT_DSN` | File async Doctrine (`doctrine://default`) |
| `OPENWEATHER_API_KEY` | Clé API OpenWeather (chatbot météo ; mode démo si vide) |
| `OPENWEATHER_DEFAULT_CITY` | Ville par défaut (ex. `Paris,FR`) |

---

## E-mails transactionnels

Les e-mails (inscription, confirmation de réservation) passent par **Messenger** en mode asynchrone.

1. Déclencher une action (inscription, réservation).
2. Le worker `messenger-worker` dans Docker traite la file automatiquement.
3. Consulter les e-mails dans Mailpit : http://localhost:8025

En développement sans worker dédié : `make worker`.

---

## Tests

```bash
make test-init   # Crée app_test + migrations + fixtures
make test
```

---

## Structure du projet

```
src/
  Controller/       # Contrôleurs web et API REST v1
  DataFixtures/     # Jeu de données demo (Faker)
  Entity/           # Modèle Doctrine
  Event/            # Événements métier (e-mails)
  Service/          # Logique métier (météo, API, recherche)
templates/          # Vues Twig
migrations/         # Schéma PostgreSQL
tests/              # Tests PHPUnit
docker/             # Entrypoint et config Docker
```

---

## Dépannage

| Problème | Piste |
|----------|-------|
| Port 8091 occupé | Modifier `APP_PORT` dans `.env` à la racine (ex. `APP_PORT=8092`) |
| Erreur de connexion BDD | Vérifier `docker compose ps database` — doit être **Up (healthy)** |
| `could not translate host name "database"` | La base ne tourne pas : `docker compose up -d database --wait` |
| E-mails non reçus | Vérifier Mailpit (http://localhost:8026) et le worker Messenger |
| Identifiants refusés | Recharger les fixtures (`make fixtures`) |
| Météo en mode démo | Définir `OPENWEATHER_API_KEY` dans `.env.local` |

Réinitialiser le mot de passe d'un utilisateur :

```bash
docker compose exec php php bin/console app:user:reset-password user@example.com NouveauMotDePasse
```

---

## Licence

Projet pédagogique ESGI — 2026.
