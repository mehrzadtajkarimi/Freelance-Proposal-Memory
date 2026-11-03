# Repository guidance

- Keep code, filenames, seeders, fixtures, and public documentation in English.
- Seed only generic starter content. Never add real freelancer profiles, client data, proposal history, credentials, or private rules to the public repository.
- Keep private application data in ignored local files and database volumes. Review staged paths and content before publishing.
- Use migrations for schema changes and keep seeders idempotent.
- Treat proposal drafts and confirmed submissions as separate states.
- Do not run destructive database commands against a database that may contain user data.
- Run focused validation for changes; do not claim checks that were not run.
- The `.githooks/pre-commit` hook runs the full test suite; keep it enabled and do not commit while tests fail.

## Runtime and validation

- This project is Docker-based; PHP is not installed on the host. Do not spend time checking for or installing host PHP.
- Run PHP, Composer, Artisan, and Laravel validation commands through the `app` service, for example `docker compose exec -T app php artisan route:list`. If the container is unavailable, report that limitation instead of checking the host for PHP.
