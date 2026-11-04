<?php

namespace App\Services;

use App\Models\PaymentStageRule;
use App\Models\Rule;
use App\Models\RuleVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;

final class AgentRulePackage
{
    private const FORMAT = 'freelance-proposal-memory-rules';

    /** @return array<string, mixed> */
    public function export(): array
    {
        $rules = Rule::query()
            ->where('scope', 'global')
            ->whereIn('status', ['approved', 'pending_approval'])
            ->with(['versions' => fn ($query) => $query
                ->whereIn('status', ['approved', 'pending_approval'])
                ->orderByDesc('version')])
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->map(function (Rule $rule): ?array {
                $version = $rule->versions->firstWhere('version', $rule->current_version);

                if (! $version || ! in_array($version->status, ['approved', 'pending_approval'], true)) {
                    return null;
                }

                return [
                    'key' => $this->validKey($rule->legacy_key)
                        ? $rule->legacy_key
                        : $this->portableKey('rule', $rule->title),
                    'title' => $version->title ?? $rule->title,
                    'body' => $version->body,
                    'priority' => (int) ($version->priority ?? $rule->priority),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $paymentStageRules = PaymentStageRule::query()
            ->whereIn('status', ['approved', 'pending_approval'])
            ->orderByRaw('minimum_amount_toman IS NOT NULL')
            ->orderBy('minimum_amount_toman')
            ->get()
            ->map(fn (PaymentStageRule $rule): array => [
                'key' => $this->portableKey('payment_stage', json_encode([
                    $rule->name_fa,
                    $rule->name_en,
                    $rule->minimum_amount_toman,
                    $rule->maximum_amount_toman,
                    $rule->stages,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
                'name_fa' => $rule->name_fa,
                'name_en' => $rule->name_en,
                'minimum_amount_toman' => $rule->minimum_amount_toman,
                'maximum_amount_toman' => $rule->maximum_amount_toman,
                'stages' => collect($rule->stages)->sortBy('position')->values()->all(),
            ])
            ->all();

        return [
            'format' => self::FORMAT,
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'rules' => $rules,
            'payment_stage_rules' => $paymentStageRules,
        ];
    }

    /** @return array<string, mixed> */
    public function validateUpload(UploadedFile $file): array
    {
        return $this->validateContents($file->get());
    }

    /** @return array<string, mixed> */
    public function validateContents(string $contents): array
    {
        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'file' => __('ui.data.invalid_json'),
            ]);
        }

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'file' => __('ui.data.invalid_package'),
            ]);
        }

        $validator = Validator::make($payload, [
            'format' => ['required', 'in:'.self::FORMAT],
            'version' => ['required', 'integer', 'in:1'],
            'exported_at' => ['required', 'date'],
            'rules' => ['required', 'array', 'min:1', 'max:500'],
            'rules.*' => ['required', 'array:key,title,body,priority'],
            'rules.*.key' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/', 'distinct'],
            'rules.*.title' => ['required', 'string', 'max:255'],
            'rules.*.body' => ['required', 'string', 'max:20000'],
            'rules.*.priority' => ['required', 'integer', 'between:1,65535'],
            'payment_stage_rules' => ['required', 'array', 'max:100'],
            'payment_stage_rules.*' => ['required', 'array:key,name_fa,name_en,minimum_amount_toman,maximum_amount_toman,stages'],
            'payment_stage_rules.*.key' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/', 'distinct'],
            'payment_stage_rules.*.name_fa' => ['required', 'string', 'max:160'],
            'payment_stage_rules.*.name_en' => ['required', 'string', 'max:160'],
            'payment_stage_rules.*.minimum_amount_toman' => ['present', 'nullable', 'integer', 'min:0', 'max:9000000000000000000'],
            'payment_stage_rules.*.maximum_amount_toman' => ['present', 'nullable', 'integer', 'min:0', 'max:9000000000000000000'],
            'payment_stage_rules.*.stages' => ['required', 'array', 'min:1', 'max:20'],
            'payment_stage_rules.*.stages.*' => ['required', 'array:position,percentage,title_fa,title_en,description_fa,description_en'],
            'payment_stage_rules.*.stages.*.position' => ['required', 'integer', 'between:1,20'],
            'payment_stage_rules.*.stages.*.percentage' => ['required', 'integer', 'between:1,100'],
            'payment_stage_rules.*.stages.*.title_fa' => ['required', 'string', 'max:160'],
            'payment_stage_rules.*.stages.*.title_en' => ['required', 'string', 'max:160'],
            'payment_stage_rules.*.stages.*.description_fa' => ['required', 'string', 'max:2000'],
            'payment_stage_rules.*.stages.*.description_en' => ['required', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($payload): void {
            $ranges = [];

            foreach ($payload['payment_stage_rules'] ?? [] as $index => $rule) {
                if (! is_array($rule)) {
                    continue;
                }

                $minimum = $rule['minimum_amount_toman'] ?? null;
                $maximum = $rule['maximum_amount_toman'] ?? null;

                if ($minimum === null && $maximum === null) {
                    $validator->errors()->add("payment_stage_rules.{$index}.minimum_amount_toman", __('ui.payment_rules.range_required'));
                }

                if ($minimum !== null && $maximum !== null && (int) $minimum > (int) $maximum) {
                    $validator->errors()->add("payment_stage_rules.{$index}.maximum_amount_toman", __('ui.payment_rules.invalid_range'));
                }

                $stages = $rule['stages'] ?? [];
                if (! is_array($stages)) {
                    continue;
                }

                if (array_sum(array_map(static fn ($stage): int => is_array($stage) ? (int) ($stage['percentage'] ?? 0) : 0, $stages)) !== 100) {
                    $validator->errors()->add("payment_stage_rules.{$index}.stages", __('ui.payment_rules.percentages_must_total_100'));
                }

                $lower = $minimum === null ? 0 : (int) $minimum;
                $upper = $maximum === null ? PHP_INT_MAX : (int) $maximum;
                foreach ($ranges as [$existingLower, $existingUpper]) {
                    if ($lower <= $existingUpper && $upper >= $existingLower) {
                        $validator->errors()->add("payment_stage_rules.{$index}.minimum_amount_toman", __('ui.payment_rules.overlapping_range'));
                        break;
                    }
                }
                $ranges[] = [$lower, $upper];
            }
        });

        return $validator->validate();
    }

    /** @param array<string, mixed> $package
     *  @return array{rules_imported: int, rules_reused: int, rules_archived: int, payment_stage_rules_imported: int}
     */
    public function replace(array $package): array
    {
        return DB::transaction(function () use ($package): array {
            $existingRuleCount = Rule::query()->count();
            RuleVersion::query()
                ->whereIn('rule_id', Rule::query()->select('id'))
                ->where('status', 'pending_approval')
                ->update(['status' => 'superseded']);
            Rule::query()->update(['status' => 'archived', 'approved_at' => null]);

            $reused = 0;
            foreach ($package['rules'] as $ruleData) {
                $rule = Rule::query()->where('legacy_key', $ruleData['key'])->lockForUpdate()->first();

                if (! $rule) {
                    $rule = Rule::query()->create([
                        'legacy_key' => $ruleData['key'],
                        'scope' => 'global',
                        'title' => $ruleData['title'],
                        'status' => 'pending_approval',
                        'current_version' => 1,
                        'priority' => (int) $ruleData['priority'],
                    ]);
                    $versionNumber = 1;
                } else {
                    $versionNumber = ((int) $rule->versions()->max('version')) + 1;
                    $reused++;
                }

                $rule->forceFill([
                    'project_request_id' => null,
                    'scope' => 'global',
                    'title' => $ruleData['title'],
                    'status' => 'pending_approval',
                    'current_version' => $versionNumber,
                    'priority' => (int) $ruleData['priority'],
                    'approved_at' => null,
                ])->save();

                $rule->versions()->create([
                    'version' => $versionNumber,
                    'title' => $ruleData['title'],
                    'priority' => (int) $ruleData['priority'],
                    'body' => $ruleData['body'],
                    'status' => 'pending_approval',
                    'source_type' => 'portable_import',
                    'change_summary' => 'Imported as a replacement rule; explicit approval is required before use.',
                    'created_at' => now(),
                ]);
            }

            PaymentStageRule::query()->delete();
            foreach ($package['payment_stage_rules'] as $ruleData) {
                PaymentStageRule::query()->create([
                    'name_fa' => $ruleData['name_fa'],
                    'name_en' => $ruleData['name_en'],
                    'minimum_amount_toman' => $ruleData['minimum_amount_toman'],
                    'maximum_amount_toman' => $ruleData['maximum_amount_toman'],
                    'stages' => collect($ruleData['stages'])->sortBy('position')->values()->all(),
                    'status' => 'pending_approval',
                    'approved_at' => null,
                ]);
            }

            return [
                'rules_imported' => count($package['rules']),
                'rules_reused' => $reused,
                'rules_archived' => max(0, $existingRuleCount - $reused),
                'payment_stage_rules_imported' => count($package['payment_stage_rules']),
            ];
        });
    }

    private function portableKey(string $type, string $value): string
    {
        $prefix = $type.'_';

        return $prefix.substr(hash('sha256', $type.':'.$value), 0, 64 - strlen($prefix));
    }

    private function validKey(?string $key): bool
    {
        return $key !== null && preg_match('/^[A-Za-z0-9_-]{1,64}$/', $key) === 1;
    }
}
