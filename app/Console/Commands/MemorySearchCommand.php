<?php

namespace App\Console\Commands;

use App\Models\FreelancerProfile;
use App\Models\ProjectRequest;
use App\Services\JsonInputReader;
use App\Services\MemorySearch;
use Illuminate\Console\Command;

final class MemorySearchCommand extends Command
{
    protected $signature = 'memory:search {query? : Title, request text, or a rule phrase} {--stdin-json : Read title and text from standard input} {--project-id= : Limit project-specific rules to this request}';

    protected $description = 'Search saved project requests and approved persistent rules by relevance';

    public function handle(JsonInputReader $inputReader, MemorySearch $search): int
    {
        $input = null;
        if ($this->option('stdin-json')) {
            $input = $inputReader->decode();
            if (! is_array($input)) {
                $this->error('Standard input must contain a JSON object with title and/or text.');

                return self::INVALID;
            }
        }

        $title = is_string($input['title'] ?? null) ? trim($input['title']) : '';
        $text = is_string($input['text'] ?? null)
            ? trim($input['text'])
            : trim((string) ($this->argument('query') ?? ''));
        if ($title === '' && $text === '') {
            $this->error('Provide a title/query or non-empty JSON title/text.');

            return self::INVALID;
        }

        $projectId = null;
        if ($this->option('project-id') !== null) {
            $projectId = filter_var($this->option('project-id'), FILTER_VALIDATE_INT);
            if ($projectId === false || $projectId < 1) {
                $this->error('The project ID must be a positive integer.');

                return self::INVALID;
            }
        }

        $terms = $search->terms($title, $text);
        $exact = $title !== ''
            ? ProjectRequest::query()
                ->where('title', $title)
                ->with([
                    'proposals' => fn ($query) => $query->orderByDesc('version'),
                    'emphases',
                ])
                ->orderByDesc('updated_at')
                ->limit(10)
                ->get()
            : collect();

        $ambiguousExactTitle = $exact->count() > 1;
        if ($exact->count() === 1 || ($exact->isNotEmpty() && ($text === '' || $terms === []))) {
            $requests = $exact;
            $matchType = 'exact_title';
        } elseif ($ambiguousExactTitle && $terms !== []) {
            $requests = $search->searchRequests($terms, $search->searchText($terms), $title, $title);
            $matchType = 'ranked_exact_title';
        } elseif ($terms === []) {
            $this->error('No searchable terms remain after removing common words. Include a specific project word or phrase.');

            return self::INVALID;
        } else {
            $requests = $search->searchRequests($terms, $search->searchText($terms), $title);
            $matchType = 'text_candidates';
        }

        $rules = $terms === []
            ? collect()
            : $search->searchRules($terms, $search->searchText($terms), $projectId);

        $this->line(json_encode([
            'result' => $matchType,
            'query_terms' => $terms,
            'matches' => $requests->count(),
            'ambiguous_exact_title' => $ambiguousExactTitle,
        ], JSON_UNESCAPED_UNICODE));

        $profile = FreelancerProfile::query()->where('profile_key', 'primary')->first();
        $this->line('MANDATORY EXPERIENCE REVIEW FOR THIS REQUEST');
        $this->line(json_encode([
            'required_review' => true,
            'instruction' => 'Compare every saved experience with the supplied request title and full request text. Use directly relevant entries as factual evidence and do not infer experience from unrelated entries.',
            'experiences' => $profile?->experienceContextValues() ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->line('PROJECT REQUESTS');
        foreach ($requests as $request) {
            $isExact = $matchType === 'exact_title';
            $basis = $isExact ? ['exact_title'] : $this->requestMatchBasis($request);
            $this->line(json_encode([
                'id' => $request->id,
                'title' => $request->title,
                'platform' => $request->platform,
                'external_id' => $request->external_id,
                'status' => $request->status,
                'budget_min_amount' => $request->budget_min_amount,
                'budget_max_amount' => $request->budget_max_amount,
                'currency' => $request->currency,
                'source_url' => $request->source_url,
                'client_location_text' => $request->client_location_text,
                'client_city' => $request->client_city,
                'client_region' => $request->client_region,
                'client_country' => $request->client_country,
                'client_deadline_days' => $request->client_deadline_days,
                'client_deadline_text' => $request->client_deadline_text,
                'posted_at' => $request->posted_at,
                'posted_time_text' => $request->posted_time_text,
                'posted_at_basis' => $request->posted_at_basis,
                'captured_at' => $request->captured_at,
                'source_timezone' => $request->source_timezone,
                'client_is_new' => $request->client_is_new,
                'client_reviews_count' => $request->client_reviews_count,
                'client_rating' => $request->client_rating,
                'client_rating_scale' => $request->client_rating_scale,
                'client_payment_verified' => $request->client_payment_verified,
                'applicants_count' => $request->applicants_count,
                'is_full_time' => $request->is_full_time_known ? $request->is_full_time : null,
                'is_full_time_known' => $request->is_full_time_known,
                'is_featured' => $request->is_featured,
                'emphases' => $request->emphases->map->only(['position', 'title', 'source_excerpt', 'interpretation']),
                'metadata' => $request->metadata,
                'match_score' => $isExact ? 10000 : $this->requestScore($request),
                'match_basis' => $basis,
                'request' => mb_substr($request->request_body, 0, 1200),
                'latest_proposal' => $request->proposals->first()?->only(['version', 'status', 'content_locale', 'key_idea', 'body', 'price_amount', 'currency', 'delivery_days', 'estimate']),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $this->line('APPROVED RULES');
        foreach ($rules as $rule) {
            $version = $rule->versions->firstWhere('version', $rule->current_version);
            $this->line(json_encode([
                'id' => $rule->id,
                'scope' => $rule->scope,
                'title' => $version?->title ?? $rule->title,
                'version' => $version?->version,
                'priority' => $version?->priority ?? $rule->priority,
                'match_score' => $this->ruleScore($rule),
                'match_basis' => $this->ruleMatchBasis($rule),
                'body' => $version?->body,
                'source_type' => $version?->source_type,
                'source_reference' => $version?->source_reference,
                'change_summary' => $version?->change_summary,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function requestMatchBasis(ProjectRequest $request): array
    {
        $basis = [];
        if ($this->scoreAttribute($request, 'title_phrase_score') > 0) {
            $basis[] = 'title_phrase';
        }
        if ($this->scoreAttribute($request, 'title_score') > 0) {
            $basis[] = 'title';
        }
        if ($this->scoreAttribute($request, 'request_score') > 0) {
            $basis[] = 'request_text';
        }
        foreach ([
            'proposal_score' => 'proposal_history',
            'message_score' => 'client_messages',
            'legacy_score' => 'legacy_source',
        ] as $attribute => $label) {
            if ($this->scoreAttribute($request, $attribute) > 0) {
                $basis[] = $label;
            }
        }

        return $basis;
    }

    private function requestScore(ProjectRequest $request): float
    {
        if (! array_key_exists('title_score', $request->getAttributes())) {
            return $this->scoreAttribute($request, 'request_score') * 8
                + $this->scoreAttribute($request, 'title_phrase_score')
                + $this->scoreAttribute($request, 'proposal_score') * 3
                + $this->scoreAttribute($request, 'message_score') * 2
                + $this->scoreAttribute($request, 'legacy_score');
        }

        return $this->scoreAttribute($request, 'title_phrase_score')
            + $this->scoreAttribute($request, 'title_score') * 8
            + $this->scoreAttribute($request, 'request_score') * 4
            + $this->scoreAttribute($request, 'proposal_score') * 3
            + $this->scoreAttribute($request, 'message_score') * 2
            + $this->scoreAttribute($request, 'legacy_score');
    }

    /** @return list<string> */
    private function ruleMatchBasis(object $rule): array
    {
        $basis = [];
        if ($this->scoreAttribute($rule, 'title_score') > 0) {
            $basis[] = 'rule_title';
        }
        if ($this->scoreAttribute($rule, 'body_score') > 0) {
            $basis[] = 'rule_body';
        }

        return $basis;
    }

    private function ruleScore(object $rule): float
    {
        return $this->scoreAttribute($rule, 'title_score') * 5
            + $this->scoreAttribute($rule, 'body_score') * 2;
    }

    private function scoreAttribute(object $model, string $attribute): float
    {
        return (float) ($model->getAttribute($attribute) ?? 0);
    }
}
