# Repository guidance

- Keep code, filenames, seeders, fixtures, and public documentation in English.
- Seed only generic starter content. Never add real freelancer profiles, client data, proposal history, credentials, or private rules to the public repository.
- Keep private application data in ignored local files and database volumes. Review staged paths and content before publishing.
- Use migrations for schema changes and keep seeders idempotent.
- Treat proposal drafts and confirmed submissions as separate states.
- Do not run destructive database commands against a database that may contain user data.
- Run focused validation for changes; do not claim checks that were not run.
