# Book Catalog

Yii2 application for managing a catalog of books, authors, subscriptions, cover uploads, and notifications.

## Features

- CRUD for books and authors
- many-to-many book/author relations
- cover image upload with configurable storage driver
- subscription notifications by email and SMS
- service and repository layers over the Yii web app
- unit and functional tests on Codeception

## Requirements

- PHP 8.2+
- MySQL
- Composer

## Configuration

1. Copy `.env.example` to `.env`
2. Set at minimum:

```dotenv
YII_ENV=dev
YII_DEBUG=1
COOKIE_VALIDATION_KEY=your-random-secret
DOCKER_ENV=0
```

3. Configure optional integrations when needed:

```dotenv
STORAGE_DRIVER=local
SMS_API_KEY=
S3_BUCKET=
S3_ACCESS_KEY=
S3_SECRET_KEY=
```

`COOKIE_VALIDATION_KEY` is required. The application will not start without it.

## Database

Default DB config is defined in [config/db.php](/D:/projects/test-book-yii2/config/db.php) and switches between local MySQL and docker MySQL via `DOCKER_ENV`.

Run migrations:

```bash
php yii migrate
```

## Run locally

Install dependencies:

```bash
composer install
```

Start the built-in server:

```bash
php yii serve
```

Default entry route is the books list.

## Tests

Run all available tests:

```bash
php vendor/bin/codecept run
```

Run only unit tests:

```bash
php vendor/bin/codecept run unit
```

Note: some legacy functional auth tests still require a MySQL-backed test environment from [config/test_db.php](/D:/projects/test-book-yii2/config/test_db.php).

## Project structure

- `controllers/` web controllers
- `models/` ActiveRecord and form models
- `services/` application and infrastructure services
- `repositories/` data access helpers
- `components/` DI bootstrap and framework glue
- `views/` server-rendered UI
- `tests/` Codeception suites

## Storage and notifications

- Storage is selected by `STORAGE_DRIVER`
- `local` uses local filesystem storage
- S3-compatible storage is configured through `S3_*` variables
- SMS integration is optional and configured through `SMS_*` variables
