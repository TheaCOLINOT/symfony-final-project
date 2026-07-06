#!/bin/sh
set -e

if [ ! -f .env ]; then
  echo "==> Aucun .env trouvé, copie de .env.example..."
  cp .env.example .env
  SECRET=$(php -r "echo bin2hex(random_bytes(16));")
  sed -i "s/APP_SECRET=.*/APP_SECRET=${SECRET}/" .env
  echo "==> APP_SECRET généré automatiquement."
fi

# Priorité à la variable injectée par Docker Compose
export DATABASE_URL="${DATABASE_URL:-postgresql://app:my-super-secret-password@database:5432/app?serverVersion=16&charset=utf8}"

echo "==> Création des dossiers var/..."
mkdir -p var/cache var/log

echo "==> Installation des dépendances Composer..."
composer install --no-interaction --prefer-dist --no-scripts

echo "==> Génération des autoloaders..."
composer dump-autoload --no-interaction

echo "==> Warm-up du cache Symfony..."
php bin/console cache:warmup

wait_for_database() {
  echo "==> Attente de PostgreSQL (hôte database:5432)..."
  attempt=1
  max_attempts=45
  while [ "$attempt" -le "$max_attempts" ]; do
    if pg_isready -h database -p 5432 -U app -d app >/dev/null 2>&1; then
      echo "==> PostgreSQL répond."
      if php bin/console dbal:run-sql "SELECT 1" --quiet >/dev/null 2>&1; then
        echo "==> Connexion Doctrine OK."
        return 0
      fi
      echo "   PostgreSQL joignable mais Doctrine échoue (vérifiez DATABASE_URL)..."
      php bin/console dbal:run-sql "SELECT 1" 2>&1 | head -n 3 || true
    else
      echo "   Tentative ${attempt}/${max_attempts} — PostgreSQL indisponible..."
    fi
    sleep 2
    attempt=$((attempt + 1))
  done
  echo "==> ERREUR : base de données inaccessible."
  echo "    DATABASE_URL=${DATABASE_URL}"
  echo "    Vérifiez que le conteneur database tourne : docker compose ps database"
  exit 1
}

wait_for_database

echo "==> Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "==> Création du compte administrateur par défaut..."
php bin/console app:create-admin-user --no-interaction

echo "==> Application prête."
touch /tmp/app-ready

echo "==> Démarrage du serveur PHP sur le port 8000..."
exec php -S 0.0.0.0:8000 -t public
