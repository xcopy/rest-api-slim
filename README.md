# REST API Slim

Slim 4 REST API with token authentication, email verification, CORS, and Redis-backed rate limiting.

## Setup

### Docker

```sh
docker compose up --build -d
docker compose exec app composer db:fresh
docker compose exec app composer db:seed
```

The API is available at <http://localhost:8000>. Compose starts the PHP app, MySQL, and Redis. The app connects to the `mysql` and `redis` services; MySQL is also available from the host on port `3307`. On its first start with an empty data volume, MySQL creates the application, local, and testing databases. The source is bind-mounted into the app container, while Composer dependencies and MySQL data use named volumes.

Compose sets `APP_ENV=local` and database/Redis connection values for the app. Adjust these values in `docker-compose.yml` for your local setup. The bundled PHP development server is not intended for production.

### Native

Requirements: PHP 8.3+, Composer, MySQL, and Redis.

```sh
composer install
cp .env.dist .env.local
APP_ENV=local composer db:fresh
APP_ENV=local composer db:seed
APP_ENV=local php -S localhost:8000 -t public
```

Configure the MySQL and Redis connection values in `.env.local` for your services. Seeding replaces the sample users and role/permission assignments. In the local environment, sent emails are written as `.eml` files to `logs/mail`; other environments use the transport configured by `MAILER_DSN`.

`app/bootstrap.php` selects the environment file before loading it: `APP_ENV=local` loads `.env.local`, `APP_ENV=testing` loads `.env.testing`, and `APP_ENV=production` loads `.env`. If `APP_ENV` is not set in the process, it defaults to `production`. Environment variables set by the process or Docker take precedence over values in the selected file.

## Tests

Run the test suite with Composer. This prepares the testing database (migrations and seed data) before running Pest:

```sh
composer test
```

To run Pest without preparing the testing database, use `composer test:pest` after preparing it.

## Endpoints

- `GET /health`
- `POST /auth/register` with `{"email":"user@example.com","password":"...","password_repeat":"...","first_name":"...","last_name":"..."}`
- `POST /auth/login`, `POST /auth/logout`, `POST /auth/logout-all`
- `POST /email-verification/send` — authenticated
- `POST /email-verification/verify` with `{"token":"<token from email>"}`
- `POST /password/change` with `{"current_password":"...","new_password":"...","new_password_repeat":"..."}` — authenticated
- `POST /password/forgot` with `{"email":"user@example.com"}`
- `POST /password/reset` with `{"token":"<token from email>","new_password":"...","new_password_repeat":"..."}`
- `/user` — authenticated: `GET` to view your own account, `POST` or `PATCH` to update it, and `DELETE` to delete it

Send the access token returned by login as a bearer token in the `Authorization` header for protected endpoints: `Authorization: Bearer <access_token>`.

Registration requires `password_repeat` to match `password`; passwords must be at least 8 characters. First and last names must be 3–100 characters, using only ASCII letters, digits, and hyphens. Email addresses must be valid and unique. The password confirmation is used only for validation and is not stored.

New users must verify their email before login. Verification tokens expire according to `EMAIL_VERIFICATION_TTL`; requesting a replacement token invalidates earlier tokens. Changing a user's email address requires verification again and revokes that user's existing access tokens. The email-verification send endpoint returns `404` when the account does not exist and `409` when its email address is already verified.

Changing a password requires the current password, is limited to once every 24 hours, and revokes all of the user's existing access tokens. Email-verification and password-reset tokens expire according to `EMAIL_VERIFICATION_TTL` and `PASSWORD_RESET_TTL`, respectively; requesting a replacement token invalidates earlier tokens. Password-reset requests return `404` when the account does not exist. Resetting a password consumes the token and revokes all of the user's existing access tokens. In the local environment, issued verification and password-reset tokens are also included in their responses.

Database migrations and seeders are managed with Phinx. Configure `DB_*` in the selected environment file or process environment to connect to MySQL.
