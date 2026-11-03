<?php

namespace App\Actions;

use App\Models\ProjectRequest;
use App\Models\ProposalVersion;
use App\Models\Rule;
use App\Models\RuleVersion;
use App\Services\WorkflowRecorder;
use Illuminate\Support\Facades\DB;

final class CreateProposalDraft
{
    public function __construct(private readonly WorkflowRecorder $recorder) {}

    /** @param array<int, RuleVersion> $ruleVersions */
    public function handle(
        ProjectRequest $request,
        string $body,
        string $priceAmount,
        string $currency,
        int $deliveryDays,
        array $paymentTerms,
        array $estimate,
        string $assumptions,
        array $ruleVersions,
        string $actor = 'agent',
    ): ProposalVersion {
        if (! preg_match('/^\d+(?:\.\d{1,4})?$/D', $priceAmount)
            || (float) $priceAmount <= 0
            || ! preg_match('/^[A-Z]{3}$/D', $currency)
            || $deliveryDays < 1) {
            throw new \InvalidArgumentException('A proposal draft requires a positive price and delivery duration.');
        }

        return DB::transaction(function () use ($request, $body, $priceAmount, $currency, $deliveryDays, $paymentTerms, $estimate, $assumptions, $ruleVersions, $actor): ProposalVersion {
            $request = ProjectRequest::query()->lockForUpdate()->findOrFail($request->id);
            $ruleIds = collect($ruleVersions)->map(fn (RuleVersion $version): int => (int) $version->rule_id)
                ->unique()
                ->sort()
                ->values();
            $rules = $ruleIds->isEmpty()
                ? collect()
                : Rule::query()->whereKey($ruleIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            foreach ($ruleVersions as $ruleVersion) {
                $rule = $rules->get($ruleVersion->rule_id);
                if ($ruleVersion->status !== 'approved' || $rule?->status !== 'approved' || $rule->current_version !== $ruleVersion->version) {
                    throw new \InvalidArgumentException('Only the currently active, approved rule version can be attached to a proposal run.');
                }
            }

            $version = ((int) $request->proposals()->max('version')) + 1;
            $proposal = $request->proposals()->create([
                'version' => $version,
                'status' => 'draft',
                'body' => $body,
                'price_amount' => $priceAmount,
                'currency' => $currency,
                'delivery_days' => $deliveryDays,
                'delivery_unit' => 'calendar_day',
                'payment_terms' => $paymentTerms,
                'estimate' => $estimate,
                'assumptions' => $assumptions,
                'created_at' => now(),
            ]);

            $run = $this->recorder->start(
                'proposal_generation',
                $request,
                $actor,
                ['proposal_version_id' => $proposal->id, 'proposal_version' => $proposal->version],
                $ruleVersions,
                $proposal,
            );

            $this->recorder->event(
                $run,
                $request,
                'proposal.drafted',
                'completed',
                'A proposal draft was saved; it has not been submitted.',
                ['proposal_version_id' => $proposal->id, 'proposal_version' => $proposal->version],
                $actor,
            );
            $this->recorder->finish($run, 'succeeded', 'Draft saved; platform submission is not confirmed.');

            return $proposal;
        });
    }
}
