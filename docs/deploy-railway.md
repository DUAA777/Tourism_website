# Railway Deployment

This project should be deployed on Railway as 4 services:

1. Laravel website
2. Flask chatbot service
3. Flask similarity service
4. MySQL database

The two Python services are already split correctly:

- Chatbot service entrypoint: `resources/python/chatbot_api.py`
- Similarity service entrypoint: `resources/python/main.py`

Do not deploy `resources/python/services/restaurants.py` directly. It is a library used by `main.py`, not the Flask app entrypoint.

## 1. Push this repo to GitHub

Push the Laravel project root:

- `app/`
- `bootstrap/`
- `config/`
- `database/`
- `docs/`
- `public/`
- `resources/`
- `routes/`
- `tests/`
- `artisan`
- `composer.json`
- `package.json`

Do not push:

- `.env`
- `vendor/`
- `node_modules/`
- `storage/logs/*`

## 2. Railway MySQL

Create a MySQL service in Railway first.

Use those credentials in:

- Laravel service env
- Similarity Flask service env
- Chatbot Flask service env only if you later make chatbot DB-aware

This codebase already supports MySQL through these variables:

- `DB_CONNECTION=mysql`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

## 3. Laravel service

Root directory:

```txt
/
```

Build command:

```bash
composer install --no-dev --optimize-autoloader && npm install && npm run build
```

Start command:

```bash
php artisan serve --host=0.0.0.0 --port=$PORT
```

Laravel env vars:

```env
APP_NAME="Yalla Nemshi"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY
APP_DEBUG=false
APP_URL=https://your-laravel-service.up.railway.app

DB_CONNECTION=mysql
DB_HOST=YOUR_RAILWAY_MYSQL_HOST
DB_PORT=3306
DB_DATABASE=YOUR_RAILWAY_MYSQL_DB
DB_USERNAME=YOUR_RAILWAY_MYSQL_USER
DB_PASSWORD=YOUR_RAILWAY_MYSQL_PASSWORD

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=file

CHATBOT_SERVICE_BASE_URL=https://your-chatbot-service.up.railway.app
SIMILARITY_SERVICE_BASE_URL=https://your-similarity-service.up.railway.app

MAIL_MAILER=...
MAIL_HOST=...
MAIL_PORT=...
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
CONTACT_FORM_TO_ADDRESS=...
```

After the Laravel service is up, run:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If your real production data is not in seeders, import it into MySQL instead of relying only on `db:seed`.

## 4. Chatbot Flask service

Root directory:

```txt
resources/python
```

Build command:

```bash
pip install -r requirements.txt
```

Start command:

```bash
gunicorn chatbot_api:app --bind 0.0.0.0:$PORT --timeout 120
```

Chatbot service env vars:

```env
GEMINI_API_KEY=YOUR_GEMINI_KEY
GEMINI_MODEL=gemini-2.5-flash-lite
```

Optional if needed later:

```env
DB_CONNECTION=mysql
DB_HOST=YOUR_RAILWAY_MYSQL_HOST
DB_PORT=3306
DB_DATABASE=YOUR_RAILWAY_MYSQL_DB
DB_USERNAME=YOUR_RAILWAY_MYSQL_USER
DB_PASSWORD=YOUR_RAILWAY_MYSQL_PASSWORD
```

Health URLs:

- `/`
- `/health`
- `/chat`

## 5. Similarity Flask service

Root directory:

```txt
resources/python
```

Build command:

```bash
pip install -r requirements.txt
```

Start command:

```bash
gunicorn main:app --bind 0.0.0.0:$PORT --timeout 120
```

Similarity service env vars:

```env
DB_CONNECTION=mysql
DB_HOST=YOUR_RAILWAY_MYSQL_HOST
DB_PORT=3306
DB_DATABASE=YOUR_RAILWAY_MYSQL_DB
DB_USERNAME=YOUR_RAILWAY_MYSQL_USER
DB_PASSWORD=YOUR_RAILWAY_MYSQL_PASSWORD

SIMILARITY_SERVICE_HOST=0.0.0.0
SIMILARITY_SERVICE_DEBUG=false
```

Health URLs:

- `/`
- `/health`
- `/test`
- `/stats`

Main API URLs Laravel uses:

- `/similar/{restaurant_id}`
- `/similar-hotels/{hotel_id}`

## 6. Critical integration detail

Laravel must point to deployed service base URLs, not localhost.

Correct:

```env
CHATBOT_SERVICE_BASE_URL=https://your-chatbot-service.up.railway.app
SIMILARITY_SERVICE_BASE_URL=https://your-similarity-service.up.railway.app
```

Wrong:

```env
CHATBOT_SERVICE_BASE_URL=http://127.0.0.1:5000
SIMILARITY_SERVICE_BASE_URL=http://127.0.0.1:5001
```

This project already reads those env vars through:

- `config/services.php`

So you do not need code changes for deployed URLs after setting the Railway env vars correctly.

## 7. Smoke test order

Test in this order:

1. MySQL exists
2. Similarity service `/health`
3. Chatbot service `/health`
4. Laravel home page
5. Login/register
6. Hotels page
7. Restaurants page
8. Similar restaurants
9. Similar hotels
10. Chatbot
11. Admin dashboard

## 8. Fast rollback plan

If Railway blocks you before the presentation:

1. Run Laravel locally with `php artisan serve`
2. Run chatbot locally
3. Run similarity service locally
4. Tunnel the Laravel app with `ngrok http 8000`

That is acceptable for a live demo, but not as your final hosted deployment.
