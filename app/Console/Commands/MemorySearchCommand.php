<?php

namespace App\Console\Commands;

use App\Models\ProjectRequest;
use App\Models\Rule;
use App\Services\JsonInputReader;
use Illuminate\Console\Command;

final class MemorySearchCommand extends Command
{
    protected $signature = 'memory:search {query? : Title, request text, or a rule phrase} {--stdin-json : Read title and text from standard input} {--project-id= : Limit project-specific rules to this request}';

    protected $description = 'Search saved project requests and approved persistent rules';

    public function handle(JsonInputReader $inputReader): int
    {
        $input = null;
        if ($this->option('stdin-json')) {
            $input = $inputReader->decode();
            if (! is_array($input)) {
                $this->error('Standard input must contain a JSON object with title and/or text.');

                return self::INVALID;
            }
        }

        $title = trim((string) ($input['title'] ?? ''));
        $text = trim((string) ($input['text'] ?? $this->argument('query') ?? ''));
        if ($title === '' && $text === '') {
            $this->error('Provide a title/query or non-empty JSON title/text.');

            return self::INVALID;
        }

        $terms = collect(preg_split('/[\s،؛,.!?؟]+/u', trim($title.' '.$text)) ?: [])
            ->map(fn (string $term): string => trim($term))
            ->filter(fn (string $term): bool => mb_strlen($term) >= 3)
            ->unique()
            ->take(16)
            ->values();

        $exact = $title !== ''
            ? ProjectRequest::query()
                ->where('title', $title)
                ->with(['proposals' => fn ($query) => $query->orderByDesc('version')])
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get()
            : collect();

        if ($exact->isNotEmpty()) {
            $requests = $exact;
            $matchType = 'exact_title';
        } else {
            $requests = ProjectRequest::query()
                ->where(function ($query) use ($terms): void {
                    foreach ($terms as $term) {
                        $pattern = '%'.$term.'%';
                        $query->orWhere('title', 'like', $pattern)
                            ->orWhere('request_body', 'like', $pattern)
                            ->orWhereHas('legacyDocument', fn ($legacy) => $legacy->where('body', 'like', $pattern))
                            ->orWhereHas('proposals', fn ($proposal) => $proposal->where('body', 'like', $pattern))
                            ->orWhereHas('messages', fn ($message) => $message->where('body', 'like', $pattern));
                    }
                })
                ->with(['proposals' => fn ($query) => $query->orderByDesc('version')])
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get();
            $matchType = 'text_candidates';
        }

        $rules = Rule::query()
            ->where('status', 'approved')
            ->when($this->option('project-id'), function ($query, $projectId): void {
                $query->where(function ($scope) use ($projectId): void {
                    $scope->where('scope', 'global')
                        ->orWhere('project_request_id', (int) $projectId);
                });
            }, fn ($query) => $query->where('scope', 'global'))
            ->where(function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $pattern = '%'.$term.'%';
                    $query->orWhere('title', 'like', $pattern)
                        ->orWhereHas('versions', fn ($version) => $version
                            ->where('status', 'approved')
                            ->whereColumn('rule_versions.version', 'rules.current_version')
                            ->where('body', 'like', $pattern));
                }
            })
            ->with(['versions' => fn ($query) => $query->where('status', 'approved')->orderByDesc('version')])
            ->orderBy('priority')
            ->limit(10)
            ->get();

        $this->line(json_encode([
            'result' => $matchType,
            'matches' => $requests->count(),
            'ambiguous_exact_title' => $matchType === 'exact_title' && $requests->count() > 1,
        ], JSON_UNESCAPED_UNICODE));
        $this->line('PROJECT REQUESTS');
        foreach ($requests as $request) {
            $this->line(json_encode([
                'id' => $request->id,
                'title' => $request->title,
                'platform' => $request->platform,
                'status' => $request->status,
                'budget_min_amount' => $request->budget_min_amount,
                'budget_max_amount' => $request->budget_max_amount,
                'currency' => $request->currency,
                'request' => mb_substr($request->request_body, 0, 1200),
                'latest_proposal' => $request->proposals->first()?->only(['version', 'status', 'content_locale', 'body', 'price_amount', 'currency', 'delivery_days', 'estimate']),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $this->line('APPROVED RULES');
        foreach ($rules as $rule) {
            $version = $rule->versions->firstWhere('version', $rule->current_version);
            $this->line(json_encode([
                'id' => $rule->id,
                'scope' => $rule->scope,
                'title' => $rule->title,
                'version' => $version?->version,
                'body' => $version?->body,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }
}
