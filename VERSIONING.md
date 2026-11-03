# Versioning and releases

Freelance Proposal Memory uses Semantic Versioning 2.0.0 for release tags. The Git tag is the authoritative version; the application is not published as a Composer package.

The repository has no release tags yet. The first release should be tagged `v0.1.0` when the maintainer considers the initial feature set ready.

## Version numbers

Use tags in the form `vMAJOR.MINOR.PATCH`, with an optional pre-release suffix such as `v0.2.0-beta.1`.

Before version 1.0.0:

- Increase MINOR for new features and for changes that break existing behavior.
- Increase PATCH for backward-compatible fixes and maintenance releases.
- Clearly mark breaking changes in the changelog and release notes.

After version 1.0.0:

- Increase MAJOR for incompatible changes to supported setup, commands, data formats, or behavior.
- Increase MINOR for backward-compatible features.
- Increase PATCH for backward-compatible bug fixes.

Use Conventional Commit types as a guide: `feat` indicates a feature, `fix` a bug fix, and `BREAKING CHANGE` or `!` marks an incompatible change. The maintainer chooses the final release number.

## Preparing a release

1. Keep the `Unreleased` section in [CHANGELOG.md](CHANGELOG.md) current.
2. Before release, review migrations and setup changes, complete the required checks, and write upgrade notes for any database or configuration changes.
3. Move the shipped entries under a dated version heading in `CHANGELOG.md` using the ISO date format `YYYY-MM-DD`.
4. Merge the changelog update into `main` through a Pull Request.
5. From the merged release commit, create and push an annotated tag. For example:

   ```sh
   git tag -a v0.1.0 -m "Release v0.1.0"
   git push origin v0.1.0
   ```

6. The tag workflow creates a GitHub Release with generated notes. Review the release page and add any important migration or security details.

Only the maintainer creates release tags and publishes releases. Never move or reuse a published version tag; issue a new version instead.
