<?php

namespace App\Console\Commands;

use App\Models\FreelancerProfile;
use App\Models\LegacyDocument;
use App\Models\ProjectRequest;
use App\Models\Rule;
use Illuminate\Console\Command;

final class MemoryContextCommand extends Command
{
    protected $signature = 'memory:context {--project-id= : Include one saved project and its full history} {--locale= : Proposal output language (fa or en)} {--include-archived-bodies : Include inactive imported instruction bodies for an explicit audit}';

    protected $description = 'Load approved guidance and, when selected, one project history from the database';

    public function handle(): int
    {
        $locale = $this->option('locale') ?: config('app.locale', 'en');
        if (! in_array($locale, ['fa', 'en'], true)) {
            $this->error('The proposal output language must be fa or en.');

            return self::INVALID;
        }

        $this->line('PROPOSAL LANGUAGE');
        $this->line(json_encode(['content_locale' => $locale], JSON_UNESCAPED_UNICODE));

        $this->line('CONTEXT SOURCE MAP');
        $this->line(json_encode([
            'agent_workflow' => [
                'source' => 'AGENTS.md',
                'role' => 'Repository-level workflow and proposal-writing instructions.',
            ],
            'freelancer_facts' => [
                'source' => 'freelancer_profiles and freelancer_experiences tables',
                'role' => 'Evidence for freelancer identity, skills, and work history.',
            ],
            'approved_guidance' => [
                'source' => 'rules and rule_versions tables',
                'role' => 'Only the current approved version is active; imported changes remain pending until approved.',
            ],
            'project_history' => [
                'source' => 'project_requests, proposal_versions, conversation_messages, workflow_runs, and workflow_events tables',
                'role' => 'Project-specific facts and history; do not treat them as global rules.',
            ],
            'legacy_documents' => [
                'source' => 'legacy_documents table',
                'role' => 'Reference material; active reusable policy belongs in an approved rule version.',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $profile = FreelancerProfile::query()->where('profile_key', 'primary')->first();
        if ($profile) {
            $this->line('FREELANCER PROFILE');
            $this->line(json_encode(
                $profile->localizedValues($locale),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ));
        }

        $this->line('MANDATORY EXPERIENCE REVIEW');
        $this->line(json_encode([
            'required_for_every_request' => true,
            'instructions' => [
                'Review every saved experience against the current request title and full request text before preparing a proposal.',
                'Use only experience that directly relates to the request, and state only details supported by the saved entry.',
                'Do not imply experience in an area when no relevant saved entry exists.',
            ],
            'experiences' => $profile?->localizedExperiences($locale) ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->line('ARCHIVED REFERENCE SOURCES (NOT ACTIVE RULES)');
        $includeArchivedBodies = (bool) $this->option('include-archived-bodies');
        $archivedDocuments = LegacyDocument::query()
            ->whereIn('document_type', ['agent_instructions', 'system_guidelines'])
            ->orderBy('source_path');

        if (! $includeArchivedBodies) {
            $archivedDocuments->select(['id', 'source_path', 'title']);
        }

        $archivedDocuments->get()->each(function (LegacyDocument $document) use ($includeArchivedBodies): void {
            $reference = [
                'source' => $document->source_path,
                'title' => $document->title,
                'active_policy' => false,
                'body_included' => $includeArchivedBodies,
                'instruction' => 'Historical reference only. Do not apply as current policy; use only current approved rule versions as active reusable guidance.',
            ];

            if ($includeArchivedBodies) {
                $reference['body'] = $document->body;
            }

            $this->line(json_encode($reference, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        });

        $rules = Rule::query()
            ->where('status', 'approved')
            ->where(function ($query): void {
                $query->where('scope', 'global');
                if ($this->option('project-id')) {
                    $query->orWhere('project_request_id', (int) $this->option('project-id'));
                }
            })
            ->with(['versions' => fn ($query) => $query
                ->where('status', 'approved')
                ->orderByDesc('version')])
            ->orderBy('priority')
            ->get();

        $this->line('APPROVED RULES');
        foreach ($rules as $rule) {
            $version = $rule->versions->firstWhere('version', $rule->current_version);
            $this->line(json_encode([
                'id' => $rule->id,
                'scope' => $rule->scope,
                'title' => $version?->title ?? $rule->title,
                'version' => $rule->current_version,
                'priority' => $version?->priority ?? $rule->priority,
                'body' => $version?->body,
                'source_type' => $version?->source_type,
                'source_reference' => $version?->source_reference,
                'change_summary' => $version?->change_summary,
                'approved_at' => $version?->approved_at,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $projectId = $this->option('project-id');
        if (! $projectId) {
            return self::SUCCESS;
        }

        $project = ProjectRequest::query()
            ->with([
                'legacyDocument',
                'proposals' => fn ($query) => $query->orderByDesc('version'),
                'messages' => fn ($query) => $query->orderBy('occurred_at'),
                'workflowRuns' => fn ($query) => $query->orderBy('started_at'),
                'workflowRuns.events' => fn ($query) => $query->orderBy('occurred_at'),
                'workflowRuns.ruleVersions.rule',
                'workflowEvents' => fn ($query) => $query->orderBy('occurred_at'),
            ])
            ->find($projectId);

        if (! $project) {
            $this->error("Project request {$projectId} was not found.");

            return self::FAILURE;
        }

        $this->line('PROPOSAL TASK');
        $this->line(json_encode([
                'task' => 'Prepare a concise, tailored client-facing proposal draft for the selected project request. The app does not generate proposals or submit to freelance platforms; return text for review and manual copying only.',
            'content_locale' => $locale,
            'language_instruction' => $locale === 'fa'
                ? 'Write all client-facing text in Persian.'
                : 'Write all client-facing text in English.',
            'instructions' => [
                'Use only relevant facts from the freelancer profile and approved rules.',
                'Review every saved work experience against this project title and full request before writing; mention only directly relevant, supported experience.',
                'Do not invent skills, experience, results, credentials, prices, or delivery dates.',
                'Address the request with a concrete approach without repeating its text.',
                'Mention the possibility of continued collaboration after project completion only when this project is marked as full-time.',
                'Return a draft for review and manual copying; do not submit it or claim it has been submitted.',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->line('PROJECT HISTORY');
        $this->line(json_encode([
            'id' => $project->id,
            'title' => $project->title,
            'platform' => $project->platform,
            'status' => $project->status,
            'request_body' => $project->request_body,
            'budget_min_amount' => $project->budget_min_amount,
            'budget_max_amount' => $project->budget_max_amount,
            'currency' => $project->currency,
            'client_deadline_days' => $project->client_deadline_days,
            'is_full_time' => $project->is_full_time,
            'metadata' => $project->metadata,
            'proposals' => $project->proposals->map->only([
                'version', 'status', 'content_locale', 'body', 'price_amount', 'currency', 'delivery_days', 'payment_terms', 'estimate', 'assumptions', 'submitted_at',
            ]),
            'messages' => $project->messages->map->only(['direction', 'body', 'occurred_at']),
            'workflow_runs' => $project->workflowRuns->map(fn ($run) => [
                'id' => $run->id,
                'proposal_version_id' => $run->proposal_version_id,
                'type' => $run->run_type,
                'status' => $run->status,
                'environment' => $run->source_environment,
                'actor' => $run->actor,
                'started_at' => $run->started_at,
                'finished_at' => $run->finished_at,
                'result_summary' => $run->result_summary,
                'rules' => $run->ruleVersions->map(fn ($version) => [
                    'title' => $version->title ?? $version->rule?->title,
                    'version' => $version->version,
                    'priority' => $version->priority ?? $version->rule?->priority,
                    'body' => $version->body,
                    'source_type' => $version->source_type,
                    'source_reference' => $version->source_reference,
                    'change_summary' => $version->change_summary,
                ])->values(),
                'events' => $run->events->map->only(['event_type', 'status', 'occurred_at', 'message']),
            ]),
            'workflow_events' => $project->workflowEvents->map->only([
                'workflow_run_id', 'event_type', 'status', 'source_environment', 'actor', 'message', 'context', 'occurred_at',
            ]),
            'legacy_record' => $project->legacyDocument?->body,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
