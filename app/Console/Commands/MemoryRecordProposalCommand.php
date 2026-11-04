<?php

namespace App\Console\Commands;

use App\Actions\CreateProposalDraft;
use App\Models\ProjectRequest;
use App\Models\RuleVersion;
use App\Services\ContentSanitizer;
use App\Services\JsonInputReader;
use Illuminate\Console\Command;

final class MemoryRecordProposalCommand extends Command
{
    protected $signature = 'memory:record-proposal {projectId : Saved project request ID} {--stdin-json : Read proposal fields from standard input}';

    protected $description = 'Save a new proposal draft with a required tailored idea using active rule versions';

    public function handle(
        CreateProposalDraft $action,
        ContentSanitizer $sanitizer,
        JsonInputReader $inputReader,
    ): int
    {
        if (! $this->option('stdin-json')) {
            $this->error('Pass --stdin-json and provide body, key_idea, price_amount, currency, delivery_days, assumptions, and optional content_locale (fa or en), estimate, payment_terms, and rule_version_ids.');

            return self::INVALID;
        }

        $input = $inputReader->decode();
        if (! is_array($input)) {
            $this->error('Standard input must contain a JSON object.');

            return self::INVALID;
        }

        $priceValue = $input['price_amount'] ?? null;
        $currency = is_string($input['currency'] ?? null) ? strtoupper(trim($input['currency'])) : '';
        $deliveryValue = $input['delivery_days'] ?? null;
        $contentLocale = $input['content_locale'] ?? 'en';
        $price = is_int($priceValue) || is_float($priceValue) || is_string($priceValue)
            ? (preg_match('/^\d+(?:\.\d{1,4})?$/D', (string) $priceValue) ? (string) $priceValue : false)
            : false;
        $deliveryDays = is_int($deliveryValue) || is_string($deliveryValue) ? filter_var($deliveryValue, FILTER_VALIDATE_INT) : false;
        $estimate = is_array($input['estimate'] ?? null) ? $input['estimate'] : [];
        if (! is_string($input['body'] ?? null) || trim($input['body']) === ''
            || ! is_string($input['key_idea'] ?? null) || trim($input['key_idea']) === ''
            || mb_strlen(trim($input['key_idea'])) > 1800
            || $price === false || (float) $price <= 0
            || ! preg_match('/^[A-Z]{3}$/D', $currency)
            || $deliveryDays === false || $deliveryDays < 1
            || ! is_string($contentLocale) || ! in_array($contentLocale, ['fa', 'en'], true)
            || ! array_key_exists('assumptions', $input) || ! is_string($input['assumptions'])
            || (isset($input['payment_terms']) && ! is_array($input['payment_terms']))
            || (isset($input['rule_version_ids']) && ! is_array($input['rule_version_ids']))) {
            $this->error('JSON must include body, a non-empty key_idea of at most 1800 characters, positive price_amount, a three-letter currency code, delivery_days, assumptions text, and an optional content_locale of fa or en.');

            return self::INVALID;
        }

        $request = ProjectRequest::query()->find($this->argument('projectId'));
        if (! $request) {
            $this->error('Project request was not found.');

            return self::FAILURE;
        }

        $ruleVersionQuery = RuleVersion::query()
            ->where('rule_versions.status', 'approved')
            ->whereHas('rule', function ($query) use ($request): void {
                $query->where('status', 'approved')
                    ->whereColumn('rules.current_version', 'rule_versions.version')
                    ->where(function ($scope) use ($request): void {
                        $scope->where('scope', 'global')
                            ->orWhere('project_request_id', $request->id);
                    });
            })
            ->with('rule');

        if (isset($input['rule_version_ids'])) {
            $requestedRuleVersionIds = array_values(array_unique(array_map('intval', $input['rule_version_ids'])));
            $ruleVersionQuery->whereIn('rule_versions.id', $requestedRuleVersionIds);
        }

        $ruleVersions = $ruleVersionQuery->get()->all();
        if (isset($requestedRuleVersionIds) && count($ruleVersions) !== count($requestedRuleVersionIds)) {
            $this->error('Every requested rule version must exist and be currently approved for this project.');

            return self::INVALID;
        }

        $keyIdea = $sanitizer->redact(trim($input['key_idea']));

        $proposal = $action->handle(
            $request,
            $sanitizer->redact(trim($input['body'])),
            $price,
            $currency,
            $deliveryDays,
            $sanitizer->redactMetadata(is_array($input['payment_terms'] ?? null) ? $input['payment_terms'] : []),
            $sanitizer->redactMetadata($estimate),
            $sanitizer->redact((string) ($input['assumptions'] ?? '')),
            $ruleVersions,
            $keyIdea,
            contentLocale: $contentLocale,
        );

        $this->info(json_encode([
            'proposal_id' => $proposal->id,
            'project_request_id' => $request->id,
            'version' => $proposal->version,
            'status' => $proposal->status,
            'content_locale' => $proposal->content_locale,
        ], JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
