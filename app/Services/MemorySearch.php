<?php

namespace App\Services;

use App\Models\ProjectRequest;
use App\Models\Rule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class MemorySearch
{
    private const MAX_TERMS = 32;

    /** @var list<string> */
    private const STOP_WORDS = [
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'in', 'into', 'is', 'of', 'on', 'or', 'the', 'this', 'that', 'to', 'was', 'were', 'with', 'you', 'your', 'our',
        'و', 'در', 'از', 'به', 'با', 'که', 'این', 'برای', 'یک',
    ];

    /** @param list<string> $terms */
    public function searchRequests(array $terms, string $searchText, string $title, ?string $exactTitle = null): Collection
    {
        $query = ProjectRequest::query()
            ->select('project_requests.*')
            ->when($exactTitle !== null, fn (Builder $query) => $query->where('project_requests.title', $exactTitle))
            ->with(['proposals' => fn ($proposals) => $proposals->orderByDesc('version')]);

        if ($this->usesFullText($terms)) {
            $query->where(function (Builder $matches) use ($searchText): void {
                $matches->whereFullText(['title', 'request_body'], $searchText)
                    ->orWhereHas('legacyDocument', fn (Builder $legacy) => $legacy->whereFullText('body', $searchText))
                    ->orWhereHas('proposals', fn (Builder $proposals) => $proposals->whereFullText('body', $searchText))
                    ->orWhereHas('messages', fn (Builder $messages) => $messages->whereFullText('body', $searchText));
            });

            $query
                ->selectRaw('CASE WHEN ? <> \'\' AND project_requests.title LIKE ? THEN 100 ELSE 0 END AS title_phrase_score', [$title, '%'.$title.'%'])
                ->selectRaw('MATCH(project_requests.title, project_requests.request_body) AGAINST (? IN NATURAL LANGUAGE MODE) AS request_score', [$searchText])
                ->selectRaw('(SELECT COALESCE(MAX(MATCH(proposal_versions.body) AGAINST (? IN NATURAL LANGUAGE MODE)), 0) FROM proposal_versions WHERE proposal_versions.project_request_id = project_requests.id) AS proposal_score', [$searchText])
                ->selectRaw('(SELECT COALESCE(MAX(MATCH(conversation_messages.body) AGAINST (? IN NATURAL LANGUAGE MODE)), 0) FROM conversation_messages WHERE conversation_messages.project_request_id = project_requests.id) AS message_score', [$searchText])
                ->selectRaw('(SELECT COALESCE(MAX(MATCH(legacy_documents.body) AGAINST (? IN NATURAL LANGUAGE MODE)), 0) FROM legacy_documents WHERE legacy_documents.id = project_requests.legacy_document_id) AS legacy_score', [$searchText])
                ->orderByRaw('(title_phrase_score + request_score * 8 + proposal_score * 3 + message_score * 2 + legacy_score) DESC');
        } else {
            $query->where(function (Builder $matches) use ($terms): void {
                foreach ($terms as $term) {
                    $pattern = '%'.$term.'%';
                    $matches->orWhere('project_requests.title', 'like', $pattern)
                        ->orWhere('project_requests.request_body', 'like', $pattern)
                        ->orWhereHas('legacyDocument', fn (Builder $legacy) => $legacy->where('body', 'like', $pattern))
                        ->orWhereHas('proposals', fn (Builder $proposals) => $proposals->where('body', 'like', $pattern))
                        ->orWhereHas('messages', fn (Builder $messages) => $messages->where('body', 'like', $pattern));
                }
            });

            $query->selectRaw('CASE WHEN ? <> \'\' AND project_requests.title LIKE ? THEN 100 ELSE 0 END AS title_phrase_score', [$title, '%'.$title.'%']);

            [$sql, $bindings] = $this->likeScore('project_requests.title', $terms, 8);
            $query->selectRaw("({$sql}) AS title_score", $bindings);

            [$sql, $bindings] = $this->likeScore('project_requests.request_body', $terms, 4);
            $query->selectRaw("({$sql}) AS request_score", $bindings);

            foreach ([
                'proposal_score' => ['proposal_versions', 'proposal_versions.project_request_id = project_requests.id'],
                'message_score' => ['conversation_messages', 'conversation_messages.project_request_id = project_requests.id'],
                'legacy_score' => ['legacy_documents', 'legacy_documents.id = project_requests.legacy_document_id'],
            ] as $alias => [$table, $correlation]) {
                [$sql, $bindings] = $this->existsScore($table, $correlation, $terms);
                $query->selectRaw("({$sql}) AS {$alias}", $bindings);
            }

            $query->orderByRaw('(title_phrase_score + title_score * 8 + request_score * 4 + proposal_score * 3 + message_score * 2 + legacy_score) DESC');
        }

        return $query
            ->orderByDesc('project_requests.updated_at')
            ->limit(10)
            ->get();
    }

    /** @param list<string> $terms */
    public function searchRules(array $terms, string $searchText, ?int $projectId): Collection
    {
        $query = Rule::query()
            ->select('rules.*')
            ->where('rules.status', 'approved')
            ->when($projectId, function (Builder $scope, int $projectId): void {
                $scope->where(fn (Builder $scope) => $scope
                    ->where('rules.scope', 'global')
                    ->orWhere('rules.project_request_id', $projectId));
            }, fn (Builder $scope) => $scope->where('rules.scope', 'global'))
            ->with(['versions' => fn ($versions) => $versions
                ->where('status', 'approved')
                ->orderByDesc('version')]);

        if ($this->usesFullText($terms)) {
            $query->where(function (Builder $matches) use ($searchText): void {
                $matches->whereFullText('rules.title', $searchText)
                    ->orWhereHas('versions', fn (Builder $versions) => $versions
                        ->where('status', 'approved')
                        ->whereColumn('rule_versions.version', 'rules.current_version')
                        ->whereFullText('body', $searchText));
            });

            $query
                ->selectRaw('MATCH(rules.title) AGAINST (? IN NATURAL LANGUAGE MODE) AS title_score', [$searchText])
                ->selectRaw('(SELECT COALESCE(MAX(MATCH(rule_versions.body) AGAINST (? IN NATURAL LANGUAGE MODE)), 0) FROM rule_versions WHERE rule_versions.rule_id = rules.id AND rule_versions.status = ? AND rule_versions.version = rules.current_version) AS body_score', [$searchText, 'approved'])
                ->orderByRaw('(title_score * 5 + body_score * 2) DESC');
        } else {
            $query->where(function (Builder $matches) use ($terms): void {
                foreach ($terms as $term) {
                    $pattern = '%'.$term.'%';
                    $matches->orWhere('rules.title', 'like', $pattern)
                        ->orWhereHas('versions', fn (Builder $versions) => $versions
                            ->where('status', 'approved')
                            ->whereColumn('rule_versions.version', 'rules.current_version')
                            ->where('body', 'like', $pattern));
                }
            });

            [$sql, $bindings] = $this->likeScore('rules.title', $terms, 5);
            $query->selectRaw("({$sql}) AS title_score", $bindings);

            $bodyScore = [];
            $bodyBindings = [];
            foreach ($terms as $term) {
                $bodyScore[] = '(CASE WHEN EXISTS (SELECT 1 FROM rule_versions WHERE rule_versions.rule_id = rules.id AND rule_versions.status = ? AND rule_versions.version = rules.current_version AND rule_versions.body LIKE ?) THEN 1 ELSE 0 END)';
                array_push($bodyBindings, 'approved', '%'.$term.'%');
            }
            $query->selectRaw('('.implode(' + ', $bodyScore).') AS body_score', $bodyBindings)
                ->orderByRaw('(title_score * 5 + body_score * 2) DESC');
        }

        return $query
            ->orderBy('rules.priority')
            ->limit(10)
            ->get();
    }

    /** @return list<string> */
    public function terms(string $title, string $text): array
    {
        $titleTerms = $this->tokenize($title);
        $textFrequency = array_count_values($this->tokenize($text));
        arsort($textFrequency);

        $terms = array_values(array_unique([
            ...$titleTerms,
            ...array_keys($textFrequency),
        ]));

        return array_slice(array_map(static fn (string|int $term): string => (string) $term, $terms), 0, self::MAX_TERMS);
    }

    /** @param list<string> $terms */
    public function searchText(array $terms): string
    {
        return implode(' ', array_values(array_filter($terms, static fn (string $term): bool => mb_strlen($term) >= 3)));
    }

    /** @param list<string> $terms
     *  @return array{0: string, 1: list<string>}
     */
    private function likeScore(string $column, array $terms, int $points): array
    {
        $parts = [];
        $bindings = [];
        foreach ($terms as $term) {
            $parts[] = "(CASE WHEN {$column} LIKE ? THEN {$points} ELSE 0 END)";
            $bindings[] = '%'.$term.'%';
        }

        return [implode(' + ', $parts), $bindings];
    }

    /** @param list<string> $terms
     *  @return array{0: string, 1: list<string>}
     */
    private function existsScore(string $table, string $correlation, array $terms): array
    {
        $parts = [];
        $bindings = [];
        foreach ($terms as $term) {
            $parts[] = "(CASE WHEN EXISTS (SELECT 1 FROM {$table} WHERE {$correlation} AND {$table}.body LIKE ?) THEN 1 ELSE 0 END)";
            $bindings[] = '%'.$term.'%';
        }

        return [implode(' + ', $parts), $bindings];
    }

    /** @return list<string> */
    private function tokenize(string $text): array
    {
        $text = str_replace("\u{200C}", '', mb_strtolower($text));
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [];

        return array_values(array_filter(
            array_unique(array_map('trim', $parts)),
            static fn (string $term): bool => mb_strlen($term) >= 2 && ! in_array($term, self::STOP_WORDS, true),
        ));
    }

    /** @param list<string> $terms */
    private function usesFullText(array $terms): bool
    {
        return DB::connection()->getDriverName() === 'mysql'
            && collect($terms)->contains(static fn (string $term): bool => mb_strlen($term) >= 3);
    }
}
