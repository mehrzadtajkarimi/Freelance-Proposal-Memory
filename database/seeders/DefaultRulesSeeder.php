<?php

namespace Database\Seeders;

use App\Models\Rule;
use App\Models\RuleVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DefaultRulesSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            'Keep private estimates and notes separate from client-facing proposal text.',
            'Record a proposal as submitted only after the user confirms it was sent.',
            'Keep client-specific constraints scoped to that project unless the user approves a global rule.',
            'Do not store passwords, API keys, or unnecessary personal contact details in proposal memory.',
        ];

        DB::transaction(function () use ($rules): void {
            foreach ($rules as $position => $body) {
                $key = hash('sha256', 'default-rule:'.$body);
                $rule = Rule::query()->firstOrCreate(
                    ['legacy_key' => $key],
                    [
                        'scope' => 'global',
                        'title' => $body,
                        'status' => 'approved',
                        'current_version' => 1,
                        'priority' => ($position + 1) * 10,
                        'approved_at' => now(),
                    ],
                );

                if (! $rule->wasRecentlyCreated) {
                    continue;
                }

                RuleVersion::query()->create([
                    'rule_id' => $rule->id,
                    'version' => 1,
                    'title' => $rule->title,
                    'priority' => $rule->priority,
                    'body' => $body,
                    'status' => 'approved',
                    'source_type' => 'public_default',
                    'source_reference' => 'database/seeders/DefaultRulesSeeder.php',
                    'change_summary' => 'Generic starter rule included with the public application.',
                    'approved_at' => now(),
                    'created_at' => now(),
                ]);
            }
        });
    }
}
