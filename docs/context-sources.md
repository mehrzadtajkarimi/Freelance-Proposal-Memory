# Context source map

The workspace keeps operating instructions, freelancer facts, reusable rules, and project history in separate sources so an agent can tell policy from evidence.

## Sources and authority

1. `AGENTS.md` defines the repository agent's required workflow, including when to search, load context, save drafts, and record a confirmed submission.
2. `freelancer_profiles` and `freelancer_experiences` contain user-provided facts. Use them only to support claims about the freelancer.
3. `rules` and `rule_versions` contain reusable global or project-specific guidance. Only the current approved version is active. Proposed and imported changes remain inactive until explicitly approved.
4. `project_requests`, `request_emphases`, `proposal_versions`, `conversation_messages`, `workflow_runs`, and `workflow_events` contain project facts and history. Request records keep source/time, client/listing facts, and each requirement's source excerpt and interpretation; every new proposal version requires a tailored `key_idea`, stores it separately, and appends it under a localized heading in the client-facing body. Proposal versions also keep the freelancer's proposed amount and delivery time. A project-specific preference must not become a global rule without explicit approval.
5. `legacy_documents` preserve source material for reference. Normal context output includes only their source and title metadata because they are archived and inactive. Use `memory:context --include-archived-bodies` only for an explicit legacy-source audit. Move any reusable instruction into a rule and approve that exact version before treating it as active policy; later edits need a new approval.
6. The rules package transfers global rules and internal payment-release conditions between installations. It excludes profiles, client requests, proposals, messages, and project-specific history. Import replaces the reusable rules and places every imported rule and payment condition in pending approval; the context and matcher use only approved items.

## Agent context commands

- Run `memory:search --stdin-json` with the current request title and full text to find project candidates and applicable rules.
- Run `memory:context --locale=fa` or `--locale=en` to load the current profile, experiences, and approved global guidance.
- Archived document bodies are excluded from this normal context output; use `--include-archived-bodies` only when auditing legacy source material.
- When a project clearly matches, run `memory:context --project-id=ID --locale=...` to load that project's rules and history.
- Treat `match_score` and `match_basis` as retrieval evidence. Confirm candidate request details with project context before reusing its history.
- Keep agent-proposed rules and imported rules or changes pending until the user approves that exact version. A rule entered directly by the user during guided setup becomes active on save; later edits require fresh approval.
- The app supplies context and stores draft text for review and manual copying. It does not generate proposal text or submit proposals to freelance platforms.

If a context or search response exceeds the execution tool's output limit, redirect it to a private temporary file outside the repository, then read the file in smaller chunks. Do not treat a truncated display as complete.
