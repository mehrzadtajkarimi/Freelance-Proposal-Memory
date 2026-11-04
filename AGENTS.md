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
- In this workspace, the default command sandbox may not have Docker socket access. Start Docker commands through the approved Docker-capable command route; in Codex, set `sandbox_permissions: "require_escalated"` on `exec_command` for the Docker command. Do not retry unchanged in the restricted sandbox, change socket permissions, or check for host PHP. If the approved route is unavailable, report the limitation.
- Run database-backed memory commands inside the running `app` service with `docker compose exec -T app php artisan ...`; keep `-T` so commands work without a terminal. For `memory:search`, send the full request as JSON on stdin, for example:
  ```sh
  docker compose exec -T app php artisan memory:search --stdin-json <<'JSON'
  {"title":"<full request title>","text":"<full request text>"}
  JSON
  ```
  Keep request text out of tracked files.

## Mandatory proposal workflow

- Before drafting, revising, or responding to any freelance proposal or client negotiation, search the current request in the running application's database during that same turn. Pass its title and full request or message text to `memory:search --stdin-json`; do not rely on memory from an earlier turn.
- In that same turn, run `memory:context --locale=fa` or `--locale=en` to load the current freelancer profile, complete agent and proposal guidance, and every active approved rule. Read the full output before writing. If tool output is truncated, retrieve the remaining content before drafting.
- The initial request search and locale-only context load are independent read-only commands; run them in parallel when the execution tools support it. Wait for search results before choosing project-specific context or saving a new request, and do not parallelize database writes.
- Archived document bodies are inactive and omitted from normal context output. Use `--include-archived-bodies` only for an explicit legacy-source audit. If output still exceeds the tool display limit, save it to a private temporary file outside the repository and read every chunk before drafting.
- If search finds one clearly matching saved request, run `memory:context --project-id=ID --locale=...` and read its history and project-specific rules. Do not treat broad text candidates as a match without checking their titles and request details.
- If no reliable match exists, save the sanitized new request with `memory:record-request`, then load its context by ID before drafting. Do not attach a new request to an unrelated project.
- When saving a request, capture every available listing fact in its separate field: exact platform and source URL, budget range and currency, client deadline, location, client new/review/rating/payment-verification details, applicant count, full-time status, and featured status. Leave unavailable facts `null`; do not turn missing information into `false` or infer one field from another. A false boolean means the source confirmed a negative value. Keep the full sanitized listing in `body` and other source-specific facts in `metadata`.
- Record each emphasized requirement as a separate `emphases` entry with a short `title`, supporting `source_excerpt` when available, and a distinct `interpretation`. Do not merge several requirements into one entry or present interpretation as a source quote.
- Preserve the listing's displayed posting-time text in `posted_time_text`. For relative labels such as `25 minutes ago` or `۲۵ دقیقه پیش`, also record the observation time in `captured_at` and the applicable `source_timezone`; `memory:record-request` derives `posted_at` and marks it `derived_relative`. Supply `posted_at` when the source gives an absolute date. Keep the raw label and derivation basis so a relative display is not represented as more precise than the source.
- Keep the client's advertised deadline (`client_deadline_days` / `client_deadline_text`) distinct from the proposed delivery duration, which is stored as `delivery_days` on each proposal draft.
- Do not produce a client-facing draft until the database search and context load have succeeded and all applicable guidance and approved rules have been read in the current turn. If the database cannot be read, state that limitation; do not claim the requirements were checked.
- If imported guidance mentions retired paths or a different database/index, use this repository's current Docker app and instructions for access; keep the proposal-writing requirements themselves.
- Every client-facing proposal draft must include one concise, concrete, request-specific idea that adds value to the client's outcome. Keep it feasible and supported by the request and verified experience, then provide it separately as `key_idea`; `memory:record-proposal` adds it to the saved proposal under a localized heading.
- Follow the loaded output format, keep private estimates separate from client-facing text, and record each prepared proposal draft with `memory:record-proposal`. Never describe a draft as submitted. Only record a submission after the user confirms it was actually sent.
- The app prepares and stores proposal text for review and manual copying; it does not generate proposals or submit them to freelance platforms. Never mark a proposal submitted unless the user confirms they sent it.
- Agent-proposed rules and imported rules or changes remain inactive until the user explicitly approves that exact version. A rule the user enters directly during guided setup is active from that explicit save; every later edit needs fresh approval. Never approve a pending rule on the user's behalf or invent an approver.
