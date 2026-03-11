# RareImagery.net - Development Makefile

.PHONY: up down build install drush cr export import reindex test lint logs printful-sync
.PHONY: fe-install fe-dev-storefront fe-dev-dashboard fe-build fe-lint fe-typecheck

# Docker operations
up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

logs:
	docker compose logs -f drupal

# Drupal operations
install:
	docker compose exec drupal vendor/bin/drush site:install minimal \
		--db-url=pgsql://$${DRUPAL_DB_USER:-rareimagery}:$${DRUPAL_DB_PASSWORD:-rareimagery_secret}@postgres/$${DRUPAL_DB_NAME:-rareimagery} \
		--site-name="RareImagery.net" \
		--account-name=admin \
		--yes
	docker compose exec drupal vendor/bin/drush en rareimagery_xstore -y
	docker compose exec drupal vendor/bin/drush en jsonapi rest serialization -y
	docker compose exec drupal vendor/bin/drush theme:enable rareimagery -y
	docker compose exec drupal vendor/bin/drush config:set system.theme default rareimagery -y
	docker compose exec drupal vendor/bin/drush cr

drush:
	docker compose exec drupal vendor/bin/drush $(CMD)

cr:
	docker compose exec drupal vendor/bin/drush cr

export:
	docker compose exec drupal vendor/bin/drush config:export -y

import:
	docker compose exec drupal vendor/bin/drush config:import -y

# Search operations
reindex:
	docker compose exec drupal vendor/bin/drush search-api:clear products
	docker compose exec drupal vendor/bin/drush search-api:index products

# Testing
test:
	docker compose exec drupal vendor/bin/phpunit \
		-c web/core \
		web/modules/custom/rareimagery_xstore/tests/

# Code quality
lint:
	docker compose exec drupal vendor/bin/phpcs \
		--standard=Drupal,DrupalPractice \
		--extensions=php,module,install,test,profile,theme,info,txt,md,yml \
		web/modules/custom/rareimagery_xstore/

# Printful sync (usage: make printful-sync STORE_ID=xxx NODE_ID=123)
printful-sync:
	docker compose exec drupal vendor/bin/drush eval \
		"\Drupal::service('rareimagery_xstore.printful_sync')->syncProducts('$(STORE_ID)', $(NODE_ID));"

# Frontend operations
fe-install:
	docker compose exec node sh -c "corepack enable && pnpm install"

fe-dev-storefront:
	docker compose exec node pnpm dev:storefront

fe-dev-dashboard:
	docker compose exec node pnpm dev:dashboard

fe-build:
	docker compose exec node sh -c "corepack enable && pnpm install && pnpm build"
	docker compose exec node sh -c "cp -r /app/frontend/apps/storefront/dist /app/web/themes/custom/rareimagery/dist/storefront"
	docker compose exec node sh -c "cp -r /app/frontend/apps/dashboard/dist /app/web/themes/custom/rareimagery/dist/dashboard"

fe-lint:
	docker compose exec node pnpm lint

fe-typecheck:
	docker compose exec node pnpm typecheck
