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
- If Docker socket access is denied by the execution sandbox, use an approved Docker execution path if available. Do not change socket permissions; report the limitation if approved access is unavailable.

## Mandatory proposal workflow

- Before drafting, revising, or responding to any freelance proposal or client negotiation, search the current request in the running application's database during that same turn. Pass its title and full request or message text to `memory:search --stdin-json`; do not rely on memory from an earlier turn.
- In that same turn, run `memory:context --locale=fa` or `--locale=en` to load the current freelancer profile, complete agent and proposal guidance, and every active approved rule. Read the full output before writing. If tool output is truncated, retrieve the remaining content before drafting.
- Archived document bodies are inactive and omitted from normal context output. Use `--include-archived-bodies` only for an explicit legacy-source audit. If output still exceeds the tool display limit, save it to a private temporary file outside the repository and read every chunk before drafting.
- If search finds one clearly matching saved request, run `memory:context --project-id=ID --locale=...` and read its history and project-specific rules. Do not treat broad text candidates as a match without checking their titles and request details.
- If no reliable match exists, save the sanitized new request with `memory:record-request`, then load its context by ID before drafting. Do not attach a new request to an unrelated project.
- Do not produce a client-facing draft until the database search and context load have succeeded and all applicable guidance and approved rules have been read in the current turn. If the database cannot be read, state that limitation; do not claim the requirements were checked.
- If imported guidance mentions retired paths or a different database/index, use this repository's current Docker app and instructions for access; keep the proposal-writing requirements themselves.
- Follow the loaded output format, keep private estimates separate from client-facing text, and record each prepared proposal draft with `memory:record-proposal`. Never describe a draft as submitted. Only record a submission after the user confirms it was actually sent.
- The app prepares and stores proposal text for review and manual copying; it does not generate proposals or submit them to freelance platforms. Never mark a proposal submitted unless the user confirms they sent it.
- Agent-proposed rules and imported rules or changes remain inactive until the user explicitly approves that exact version. A rule the user enters directly during guided setup is active from that explicit save; every later edit needs fresh approval. Never approve a pending rule on the user's behalf or invent an approver.
