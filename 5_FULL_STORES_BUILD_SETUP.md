# 5. Full Stores Build – Combined Content Types + Stores Setup

**Combines**: Content types, fields, modules, demos. One-run setup.

## 0. Restart Clean
```
docker compose down -v
docker compose up -d --build
sleep 30  # Wait bootstrap
```

## 1. Install Dependencies (Commerce, AI, JSON:API)
```
docker compose exec drupal composer require \\
  drupal/commerce:^3 drupal/commerce_stripe drupal/ai drupal/ai_provider_openai drupal/key drupal/social_auth_twitter drupal/gin \\
  --no-dev --optimize-autoloader
docker compose exec drupal composer global require drush/drush:~12
docker compose exec drupal composer global config bin-dir ~/.composer/vendor/bin
docker compose exec drupal ~/.composer/vendor/bin/drush cr
```

## 2. Install Drupal (if fresh)
```
docker compose exec -u www-data drupal ~/.composer/vendor/bin/drush site:install standard \\
  --db-url=pgsql://rare_user:${POSTGRES_PASSWORD}@postgres:5432/rare_drupal \\
  --account-name=rare --account-pass=Beyondcyn1234! --yes
```

## 3. Enable Modules
```
docker compose exec drupal ~/.composer/vendor/bin/drush en commerce commerce_store jsonapi key ai social_auth_twitter gin -y
docker compose exec drupal ~/.composer/vendor/bin/drush cr
```

## 4. Create Content Types & Fields (Run Setup Script)
Download/update `rareimagery_x_import` module if needed (git pull).

```
docker compose exec drupal ~/.composer/vendor/bin/drush php modules/custom/rareimagery_x_import/setup_content_types.php
```

*(Script creates `creator_x_profile` type + all fields + bidirectional link on commerce_store)*

## 5. Create Demo Data
```
docker compose exec drupal ~/.composer/vendor/bin/drush php modules/custom/rareimagery_x_import/test_import.php
```

## 6. Test
- Admin: http://localhost:8080/user/login
- API: `curl http://localhost:8080/jsonapi/node/creator_x_profile`
- Frontend: `cd frontend && npm i && npm run dev`
- Stores: localhost:3000/stores/elonmusk

## .env Vars (add to docker-compose)
```
POSTGRES_PASSWORD=yourpass
XAI_API_KEY=yourkey
X_CONSUMER_KEY=yourkey  # Secure!
```

**Done**: Full X creator stores build. Subdomains live on Vercel deploy.

*(Extend: Add products via Commerce UI, Grok import button.)*