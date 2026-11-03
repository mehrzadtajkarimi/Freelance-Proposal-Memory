<?php

namespace App\Console\Commands;

use App\Models\FreelancerProfile;
use App\Models\LegacyDocument;
use App\Models\ProjectRequest;
use App\Models\Rule;
use Illuminate\Console\Command;

final class MemoryContextCommand extends Command
{
    protected $signature = 'memory:context {--project-id= : Include one saved project and its full history} {--locale= : Proposal output language (fa or en)}';

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

        $profile = FreelancerProfile::query()->where('profile_key', 'primary')->first();
        if ($profile) {
            $this->line('FREELANCER PROFILE');
            $this->line(json_encode(
                $profile->localizedValues($locale),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ));
        }

        $this->line('SYSTEM GUIDANCE');
        LegacyDocument::query()
            ->whereIn('document_type', ['agent_instructions', 'system_guidelines'])
            ->orderBy('source_path')
            ->get()
            ->each(fn (LegacyDocument $document) => $this->line(json_encode([
                'source' => $document->source_path,
                'title' => $document->title,
                'body' => $document->body,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)));

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
                'title' => $rule->title,
                'version' => $rule->current_version,
                'body' => $version?->body,
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
            'task' => 'Prepare a concise, tailored client-facing proposal draft for the selected project request.',
            'content_locale' => $locale,
            'language_instruction' => $locale === 'fa'
                ? 'Write all client-facing text in Persian.'
                : 'Write all client-facing text in English.',
            'instructions' => [
                'Use only relevant facts from the freelancer profile and approved rules.',
                'Do not invent skills, experience, results, credentials, prices, or delivery dates.',
                'Address the request with a concrete approach without repeating its text.',
                'Return a draft for review; do not claim it has been submitted.',
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
                    'title' => $version->rule?->title,
                    'version' => $version->version,
                    'body' => $version->body,
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
