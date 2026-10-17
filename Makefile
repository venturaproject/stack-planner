.PHONY: up down restart build logs ps \
        shell-backend shell-frontend shell-db \
        test test-unit test-frontend migrate messenger-setup migration diff console \
        install jwt-keys \
        tsc fmt fmt-check clean-volumes logs-worker \
        db-dump db-test-from-main \
        cs-fix cs-check phpstan \
        help

DC      = docker compose -f compose.dev.yml
TEST_DC = docker compose -f compose.test.yml -p boilerplate-ddd-test
PHP     = $(DC) exec backend php
CONSOLE = $(PHP) bin/console

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-24s\033[0m %s\n", $$1, $$2}'

# ── Services ──────────────────────────────────────────────────────────────────

up: ## Start all services in the background
	$(DC) up -d

down: ## Stop and remove all containers
	$(DC) down

restart: ## Restart all services
	$(DC) restart

build: ## Rebuild images
	$(DC) build

build-no-cache: ## Rebuild without cache
	$(DC) build --no-cache

logs: ## Tail logs from all services
	$(DC) logs -f

logs-backend: ## Tail backend logs only
	$(DC) logs -f backend

logs-worker: ## Tail the Messenger worker logs only
	$(DC) logs -f worker

ps: ## Container status
	$(DC) ps

# ── Shells ────────────────────────────────────────────────────────────────────

shell-backend: ## Shell into the backend container
	$(DC) exec backend sh

shell-frontend: ## Shell into the frontend container
	$(DC) exec frontend sh

shell-db: ## psql directly into postgres
	$(DC) exec postgres psql -U $${POSTGRES_USER:-postgres} -d $${POSTGRES_DB:-boilerplate_ddd}

# ── Symfony / PHP ─────────────────────────────────────────────────────────────

console: ## Run a Symfony console command: make console CMD="debug:router"
	$(CONSOLE) $(CMD)

install: ## Install Composer dependencies inside the container
	$(DC) exec backend composer install

migrate: ## Run pending migrations
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

messenger-setup: ## Create or update Messenger transport tables
	$(CONSOLE) messenger:setup-transports --no-interaction

migration: ## Generate a new blank migration: make migration NAME=CreateUser
	$(CONSOLE) doctrine:migrations:generate

diff: ## Generate a migration from entity mapping differences
	$(CONSOLE) doctrine:migrations:diff

cache-clear: ## Clear the Symfony cache
	$(CONSOLE) cache:clear

jwt-keys: ## Generate the RSA keypair used for JWT
	$(DC) exec backend php bin/console lexik:jwt:generate-keypair --skip-if-exists

seed: ## Seed the database with sample data (roles, permissions, admin)
	$(CONSOLE) app:seed

seed-force: ## Force re-seed even if data already exists
	$(CONSOLE) app:seed --force

# ── Frontend ──────────────────────────────────────────────────────────────────

install-frontend: ## Install frontend dependencies from the lockfile
	$(DC) exec frontend pnpm install --frozen-lockfile

tsc: ## TypeScript check in the frontend
	$(DC) exec frontend pnpm exec tsc --noEmit

fmt: ## Apply Prettier (reformats frontend code)
	$(DC) exec frontend pnpm run format

fmt-check: ## Check frontend formatting without modifying files (for CI)
	$(DC) exec frontend pnpm run format:check

cs-fix: ## Apply PHP-CS-Fixer (reformats code)
	$(DC) exec backend vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php

cs-check: ## Check code style without modifying files (for CI)
	$(DC) exec backend vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --diff

phpstan: ## Static analysis with PHPStan (level 10)
	$(DC) exec backend vendor/bin/phpstan analyse --memory-limit=512M

test: ## Run the full backend suite in an isolated Docker environment
	@$(TEST_DC) up --build --abort-on-container-exit --exit-code-from test; status=$$?; $(TEST_DC) down --volumes --remove-orphans; exit $$status

test-unit: ## Run backend unit tests without Docker or PostgreSQL
	cd backend && vendor/bin/phpunit tests/Unit

test-frontend: ## Run the frontend test suite
	$(DC) exec frontend pnpm test

# ── Database ──────────────────────────────────────────────────────────────────

db-dump: ## Dump the main DB to infrastructure/db/dump_<timestamp>.sql
	@mkdir -p infrastructure/db
	$(DC) exec -T postgres pg_dump \
		-U postgres \
		--no-password \
		--format=plain \
		--no-owner \
		--no-acl \
		boilerplate_ddd \
		> infrastructure/db/dump_$$(date +%Y%m%d_%H%M%S).sql
	@echo "Dump saved to infrastructure/db/"

db-test-from-main: ## Recreate the test DB from the main DB  ⚠️  wipes test data
	@mkdir -p infrastructure/db
	$(DC) exec -T postgres pg_dump \
		-U postgres \
		--no-password \
		--format=plain \
		--no-owner \
		--no-acl \
		boilerplate_ddd \
		> infrastructure/db/dump_for_test.sql
	$(DC) exec -T postgres psql -U postgres -c "DROP DATABASE IF EXISTS boilerplate_ddd_test;"
	$(DC) exec -T postgres psql -U postgres -c "CREATE DATABASE boilerplate_ddd_test WITH OWNER postgres ENCODING 'UTF8';"
	$(DC) exec -T postgres psql -U postgres -d boilerplate_ddd_test < infrastructure/db/dump_for_test.sql
	@echo "Database boilerplate_ddd_test recreated from boilerplate_ddd"

clean-volumes: ## Remove containers and volumes  ⚠️  wipes data
	$(DC) down -v
