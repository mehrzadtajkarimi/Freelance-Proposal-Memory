<?php

namespace App\Http\Controllers;

use App\Models\PaymentStageRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PaymentStageRuleController
{
    public function index(): View
    {
        $rules = PaymentStageRule::query()
            ->orderByRaw('minimum_amount_toman IS NOT NULL')
            ->orderBy('minimum_amount_toman')
            ->orderBy('maximum_amount_toman')
            ->get();

        return view('payment-rules.index', compact('rules'));
    }

    public function create(): View
    {
        $rule = new PaymentStageRule([
            'stages' => [[
                'percentage' => 100,
                'title_fa' => '',
                'title_en' => '',
                'description_fa' => '',
                'description_en' => '',
            ]],
        ]);

        return view('payment-rules.create', compact('rule'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedRule($request);
        $rule = PaymentStageRule::query()->create([
            ...$data,
            'status' => 'pending_approval',
            'approved_at' => null,
        ]);

        return redirect()->route('payment-rules.index')->with('success', __('ui.payment_rules.created', ['name' => $rule->{'name_'.app()->getLocale()}]));
    }

    public function edit(PaymentStageRule $paymentRule): View
    {
        return view('payment-rules.edit', ['rule' => $paymentRule]);
    }

    public function update(Request $request, PaymentStageRule $paymentRule): RedirectResponse
    {
        $data = $this->validatedRule($request, $paymentRule);
        $paymentRule->update([
            ...$data,
            'status' => 'pending_approval',
            'approved_at' => null,
        ]);

        return redirect()->route('payment-rules.index')->with('success', __('ui.payment_rules.updated', ['name' => $paymentRule->{'name_'.app()->getLocale()}]));
    }

    public function destroy(PaymentStageRule $paymentRule): RedirectResponse
    {
        $name = $paymentRule->{'name_'.app()->getLocale()};
        $paymentRule->delete();

        return redirect()->route('payment-rules.index')->with('success', __('ui.payment_rules.deleted', ['name' => $name]));
    }

    public function approve(PaymentStageRule $paymentRule): RedirectResponse
    {
        abort_unless($paymentRule->status === 'pending_approval', 404);

        $paymentRule->forceFill([
            'status' => 'approved',
            'approved_at' => now(),
        ])->save();

        return redirect()->route('payment-rules.index')->with('success', __('ui.payment_rules.approved'));
    }

    /** @return array{name_fa: string, name_en: string, minimum_amount_toman: ?int, maximum_amount_toman: ?int, stages: array<int, array<string, int|string>>} */
    private function validatedRule(Request $request, ?PaymentStageRule $except = null): array
    {
        $data = $request->validate([
            'name_fa' => ['required', 'string', 'max:160'],
            'name_en' => ['required', 'string', 'max:160'],
            'minimum_amount_toman' => ['nullable', 'integer', 'min:0', 'max:9000000000000000000'],
            'maximum_amount_toman' => ['nullable', 'integer', 'min:0', 'max:9000000000000000000'],
            'stages' => ['required', 'array', 'min:1', 'max:20'],
            'stages.*.percentage' => ['required', 'integer', 'between:1,100'],
            'stages.*.title_fa' => ['required', 'string', 'max:160'],
            'stages.*.title_en' => ['required', 'string', 'max:160'],
            'stages.*.description_fa' => ['required', 'string', 'max:2000'],
            'stages.*.description_en' => ['required', 'string', 'max:2000'],
        ]);

        $minimum = isset($data['minimum_amount_toman']) ? (int) $data['minimum_amount_toman'] : null;
        $maximum = isset($data['maximum_amount_toman']) ? (int) $data['maximum_amount_toman'] : null;

        if ($minimum === null && $maximum === null) {
            throw ValidationException::withMessages([
                'minimum_amount_toman' => __('ui.payment_rules.range_required'),
            ]);
        }

        if ($minimum !== null && $maximum !== null && $minimum > $maximum) {
            throw ValidationException::withMessages([
                'maximum_amount_toman' => __('ui.payment_rules.invalid_range'),
            ]);
        }

        if (array_sum(array_map(static fn (array $stage): int => (int) $stage['percentage'], $data['stages'])) !== 100) {
            throw ValidationException::withMessages([
                'stages' => __('ui.payment_rules.percentages_must_total_100'),
            ]);
        }

        $hasOverlap = PaymentStageRule::query()
            ->whereIn('status', ['approved', 'pending_approval'])
            ->when($except, fn ($query) => $query->where('id', '!=', $except->getKey()))
            ->get(['id', 'minimum_amount_toman', 'maximum_amount_toman'])
            ->contains(function (PaymentStageRule $existing) use ($minimum, $maximum): bool {
                $lowerBoundsOverlap = $minimum === null
                    || $existing->maximum_amount_toman === null
                    || $minimum <= $existing->maximum_amount_toman;
                $upperBoundsOverlap = $maximum === null
                    || $existing->minimum_amount_toman === null
                    || $existing->minimum_amount_toman <= $maximum;

                return $lowerBoundsOverlap && $upperBoundsOverlap;
            });

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'minimum_amount_toman' => __('ui.payment_rules.overlapping_range'),
            ]);
        }

        $stages = collect($data['stages'])->values()->map(fn (array $stage, int $position): array => [
            'position' => $position + 1,
            'percentage' => (int) $stage['percentage'],
            'title_fa' => trim($stage['title_fa']),
            'title_en' => trim($stage['title_en']),
            'description_fa' => trim($stage['description_fa']),
            'description_en' => trim($stage['description_en']),
        ])->all();

        return [
            'name_fa' => trim($data['name_fa']),
            'name_en' => trim($data['name_en']),
            'minimum_amount_toman' => $minimum,
            'maximum_amount_toman' => $maximum,
            'stages' => $stages,
        ];
    }
}
