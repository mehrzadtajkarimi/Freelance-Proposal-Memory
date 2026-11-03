# Freelance Proposal Memory

A self-hosted application for organizing freelance project requests, proposal drafts, client messages, private estimates, reusable rules, and workflow history.

The current release includes a bilingual Persian/English web dashboard for reviewing saved requests, reports, and versioned rules, alongside Artisan commands for recording and retrieving proposal context. It does not generate proposals with an AI provider or connect to freelance marketplaces yet; `memory:context` can provide saved context to a separate AI workflow.

## Features

- Store project requests and their platform, status, budget, and metadata.
- Keep versioned proposal drafts, delivery estimates, assumptions, and payment terms.
- Record incoming and outgoing client messages against a project.
- Maintain global and project-specific rules with explicit approval for rule changes.
- Store one private freelancer profile with separate Persian and English details, technologies, services, and languages for proposal context.
- Track workflow runs and events, including the environment and rule versions used.
- Export approved global rules to a local Markdown file.
- Run with MySQL and Redis in Docker Compose, or use SQLite for a lightweight local setup.

## Requirements

- PHP 8.3 or later and Composer, or Docker with the Compose plugin.
- MySQL for the included Docker setup. SQLite is also supported by Laravel.

## Quick start with Docker

```sh
cp .env.example .env
```

Set unique values for `DB_PASSWORD` and `MYSQL_ROOT_PASSWORD` in `.env`, then start the application:

```sh
docker compose up -d --build
```

The app is available at `http://localhost:8084`. The database and Redis are bound to localhost. Change the `*_PORT` values in `.env` if those ports are already in use.

On first startup, the container installs Composer dependencies, creates an application key when needed, and runs migrations with the generic starter rules. Those rules contain no user profile or client data. Do not point automatic migrations at a database that contains data you need to preserve; set `RUN_MIGRATIONS=false` for an existing database until you have reviewed the migrations.

## Local PHP setup

Install dependencies, prepare the environment, and create a SQLite database:

```sh
composer install
cp .env.example .env
touch database/database.sqlite
```

Set `DB_CONNECTION=sqlite` and `DB_DATABASE=database/database.sqlite` in `.env`, then run:

```sh
php artisan key:generate
php artisan migrate --seed
```

Install and build the web assets, then start the Laravel server:

```sh
npm install
npm run build
php artisan serve
```

For active frontend development, run `npm run dev` in a second terminal instead of building production assets.

## Memory commands

Use the Laravel command directly, or prefix it with `docker compose exec -T app` when running in Docker.

```sh
php artisan memory:search --stdin-json
php artisan memory:context
php artisan memory:record-request
php artisan memory:record-proposal
php artisan memory:record-message
php artisan memory:report
php artisan memory:export-rules
```

Request and proposal commands accept JSON through standard input; rule-management and reporting commands use command options. Run a command with `--help` for its exact arguments. The profile form edits the Persian or English profile according to the selected panel language. `memory:context --locale=fa` or `memory:context --locale=en` returns the matching profile and proposal-language instructions, along with approved rules and optional project-specific history. Proposal JSON may include `content_locale` (`fa` or `en`); drafts retain that locale. This prepares context for an external AI workflow; the app does not call an AI provider. The freelancer profile is kept in the local database and is not included in public seed data. A proposal draft is not marked as submitted unless the submission command is explicitly run.

For a request with a USD budget, pass `title`, `body`, `platform`, `budget_min_amount`, `budget_max_amount`, and `currency` in the JSON object. When saving a proposal, pass `body`, `price_amount`, a three-letter currency code such as `USD`, `delivery_days`, and `assumptions`; `content_locale` is optional and defaults to English. `estimate` and `payment_terms` are optional JSON objects.

The `/health` endpoint returns application readiness and database connectivity as JSON. On first Docker startup, frontend dependencies are installed and Tailwind assets are built when they are missing. For local development, install Node.js and run `npm install` followed by `npm run dev` or `npm run build`.

## Privacy and backups

Proposal requests, messages, estimates, and custom rules may contain confidential information. Keep `.env`, local database files, SQL dumps, and private exports out of Git. The public seeders only create generic starter rules. Never add client records, personal profiles, credentials, or private proposal guidelines to a public seeder or fixture.

The database volume persists when containers stop. `docker compose down -v` removes the volumes and their data. Back up the database before upgrading or removing volumes.

## Data model

The main tables are `freelancer_profiles`, `project_requests`, `proposal_versions`, `conversation_messages`, `rules`, `rule_versions`, `workflow_runs`, `workflow_events`, and `workflow_run_rule_versions`. Monetary amounts use decimal fields with a separate three-letter currency code.

## Development

Laravel source is in the standard `app/`, `config/`, `database/`, and `routes/` directories. Migrations describe the schema, and `DefaultRulesSeeder` provides safe starter rules for a new installation. Keep seed data generic and idempotent.

### Automated tests

The test suite uses an in-memory SQLite database configured in `phpunit.xml`; it does not connect to the configured MySQL database.

Run the full suite inside the Docker app container:

```sh
docker compose exec -T app composer test
```

Enable the local pre-commit test gate once per checkout:

```sh
git config core.hooksPath .githooks
```

The hook runs the full test suite and blocks a commit if tests fail or the app container is unavailable. The GitHub Actions workflow runs the same suite for pushes and pull requests; repository branch protection must require that check before merging.
