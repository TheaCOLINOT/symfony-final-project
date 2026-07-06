.DEFAULT_GOAL := help

install:
	docker compose up -d --build --wait

sh:
	docker compose exec -it php sh

cache:
	docker compose exec -it php php bin/console cache:clear

migrate:
	docker compose exec -it php php bin/console doctrine:migrations:migrate --no-interaction

fixtures:
	docker compose up -d database php --wait
	@echo "==> Attente de l'initialisation du conteneur PHP..."
	@docker compose exec -T php sh -c 'for i in $$(seq 1 90); do [ -f /tmp/app-ready ] && exit 0; sleep 2; done; echo "Timeout: le conteneur PHP ne répond pas."; exit 1'
	docker compose exec -T php php bin/console doctrine:fixtures:load --group=demo --no-interaction

init: migrate fixtures
	@echo "==> Base de données initialisée (migrations + fixtures)."

worker:
	docker compose exec -it php php bin/console messenger:consume async -vv

maintenance:
	docker compose exec php php bin/console app:reservation:expire-pending --no-interaction
	docker compose exec php php bin/console app:reservation:complete-past --no-interaction
	docker compose exec php php bin/console app:ical:sync --no-interaction

test:
	docker compose exec php php bin/phpunit

ci:
	docker compose exec -T php composer ci

# Met à jour composer.lock sans démarrer toute la stack (Docker Desktop suffit)
composer-update:
	docker run --rm -v "$(CURDIR):/app" -w /app composer:2 composer update --no-interaction

# Installe les deps localement dans vendor/ (utile si le conteneur php ne démarre pas)
composer-install:
	docker run --rm -v "$(CURDIR):/app" -w /app composer:2 composer install --no-interaction --no-scripts

test-init:
	docker compose exec database psql -U app -d app -tc "SELECT 1 FROM pg_database WHERE datname = 'app_test'" | findstr 1 >nul || docker compose exec database psql -U app -d app -c "CREATE DATABASE app_test;"
	docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction --env=test
	docker compose exec php php bin/console doctrine:fixtures:load --group=demo --no-interaction --env=test

logs:
	docker compose logs -f --tail=100 php

up start:
	docker compose up -d --wait && \
    echo "==> Les services ont été démarrés avec succès" && \
    echo "==> Application : http://localhost:8091" && \
    echo "==> Adminer (BDD) : http://localhost:8090" && \
    echo "==> Mailpit (e-mails) : http://localhost:8026"

down stop:
	docker compose down

restart: down up

help:
	@echo "Makefile commands:"
	@echo "  install  - Premier lancement : build + démarrage de tout"
	@echo "  sh       - Execute a shell inside the PHP container"
	@echo "  up       - Start the Docker containers"
	@echo "  down     - Stop and remove the Docker containers"
	@echo "  restart  - Restart the Docker containers"
	@echo "  cache    - Clear the Symfony cache"
	@echo "  migrate  - Exécuter les migrations Doctrine"
	@echo "  fixtures - Charger les fixtures (purge la BDD)"
	@echo "  init     - Migrations + fixtures (initialisation BDD)"
	@echo "  worker   - Consommer la file Messenger (emails async)"
	@echo "  maintenance - Expirer pending, compléter séjours, sync iCal"
	@echo "  test     - Lancer PHPUnit"
	@echo "  ci       - Linter Symfony + PHPStan + PHPUnit (comme GitHub Actions)"
	@echo "  test-init - Préparer la BDD de test (app_test)"
	@echo "  composer-install - composer install via image Docker (sans stack)"
	@echo "  composer-update  - composer update via image Docker (sans stack)"
	@echo "  logs     - Follow the logs of the PHP container"
	@echo "  help     - Show this help message"