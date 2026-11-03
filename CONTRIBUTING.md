# Contributing

Thank you for helping improve Freelance Proposal Memory. Contributions are welcome through GitHub Issues and Pull Requests.

## Before you start

- Search existing issues and pull requests before opening a new one.
- Open an issue before starting a large feature or a change that alters stored data or public command behavior.
- Do not include real freelancer profiles, client briefs, proposal history, credentials, private rules, or other personal data in examples, screenshots, fixtures, or seeders. Use synthetic data only.
- Report vulnerabilities through the process in [SECURITY.md](SECURITY.md), not in a public issue.

## Contribution workflow

1. Fork the repository and create a focused branch from `main`, such as `feat/search-filters`, `fix/rule-export`, or `docs/setup-guide`.
2. Keep each Pull Request focused and explain the user problem it addresses.
3. Use Conventional Commits for commit messages. Examples: `feat(memory): add request filters`, `fix(search): handle empty input`, and `docs: clarify setup`. Mark breaking changes with `!` or a `BREAKING CHANGE:` footer.
4. Update documentation and `CHANGELOG.md` when the change affects users.
5. Open a Pull Request against `main`, describe the change and validation, and link the related issue when one exists.
6. Address review feedback in the Pull Request. The maintainer reviews and merges contributions; contributors should not push directly to `main`.

## Local checks

Run the checks relevant to your change before opening a Pull Request:

```sh
composer validate --strict --no-check-publish
find . -type d \( -path './.local' -o -path './vendor' -o -path './node_modules' -o -path './.git' -o -path './storage' \) -prune -o -type f -name '*.php' -print0 | xargs -0 -r -n1 php -l
docker compose --env-file .env.example config --quiet
```

GitHub Actions runs these checks for Pull Requests and updates to `main`. The current workflow checks dependency metadata, PHP syntax, and Docker Compose configuration; a behavioral test suite has not been added yet. If a check cannot be run locally, say why in the Pull Request.

## Pull Request expectations

- Include or update tests when a test suite exists and behavior changes.
- Keep database migrations safe for existing installations and document any required upgrade steps.
- Keep public documentation and file names in English. The application may contain user-facing translations.
- Update both language versions when changing existing bilingual interface text.
- Do not commit local environment files, database dumps, generated secrets, or private memory data.
- Add screenshots for visible interface changes when practical.
- Keep the changelog curated for users; do not paste the raw commit log.

The maintainer is responsible for review, releases, and changes to `main`.
