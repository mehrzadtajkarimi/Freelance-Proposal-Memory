<?php

namespace App\Services;

use App\Models\PaymentStageRule;

final class PaymentStageRuleMatcher
{
    /** @return array<string, mixed>|null */
    public function termsFor(string $priceAmount, string $currency, string $locale): ?array
    {
        $amount = (float) $priceAmount;
        $currency = strtoupper($currency);

        if ($currency === 'IRR') {
            $amount /= 10;
        } elseif ($currency !== 'IRT') {
            return null;
        }

        $rule = PaymentStageRule::query()
            ->where('status', 'approved')
            ->orderByRaw('minimum_amount_toman IS NOT NULL')
            ->orderBy('minimum_amount_toman')
            ->get()
            ->first(fn (PaymentStageRule $candidate): bool =>
                ($candidate->minimum_amount_toman === null || $amount >= $candidate->minimum_amount_toman)
                && ($candidate->maximum_amount_toman === null || $amount <= $candidate->maximum_amount_toman)
            );

        if (! $rule) {
            return null;
        }

        return [
            'rule_id' => $rule->id,
            'rule_name' => $rule->{'name_'.$locale} ?? $rule->name_fa,
            'basis' => 'final_proposed_amount_toman',
            'amount_toman' => $amount,
            'stages' => collect($rule->stages)->sortBy('position')->values()->map(fn (array $stage): array => [
                'position' => $stage['position'],
                'percentage' => $stage['percentage'],
                'title' => $stage['title_'.$locale] ?? $stage['title_fa'],
                'description' => $stage['description_'.$locale] ?? $stage['description_fa'],
            ])->all(),
        ];
    }
}
