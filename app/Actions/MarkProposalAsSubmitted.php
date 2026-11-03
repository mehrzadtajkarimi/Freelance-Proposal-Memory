<?php

namespace App\Actions;

use App\Models\ProposalVersion;
use App\Services\WorkflowRecorder;
use Illuminate\Support\Facades\DB;

final class MarkProposalAsSubmitted
{
    public function __construct(private readonly WorkflowRecorder $recorder) {}

    /** Call only after the user confirms that the platform accepted the submission. */
    public function handle(ProposalVersion $proposal, string $confirmedBy): ProposalVersion
    {
        return DB::transaction(function () use ($proposal, $confirmedBy): ProposalVersion {
            $proposal = ProposalVersion::query()->lockForUpdate()->findOrFail($proposal->id);
            if ($proposal->status !== 'draft') {
                throw new \InvalidArgumentException('Only an unsent draft proposal can be marked as submitted.');
            }

            $proposal->forceFill([
                'status' => 'submitted',
                'submitted_at' => now(),
            ])->save();

            $request = $proposal->projectRequest;
            $request->forceFill(['status' => 'submitted'])->save();
            $run = $this->recorder->start(
                'proposal_submission',
                $request,
                $confirmedBy,
                ['proposal_version_id' => $proposal->id, 'proposal_version' => $proposal->version],
                proposalVersion: $proposal,
            );

            $this->recorder->event(
                $run,
                $request,
                'proposal.sent',
                'completed',
                'The user confirmed that the proposal was submitted on the platform.',
                ['proposal_version_id' => $proposal->id, 'proposal_version' => $proposal->version],
                $confirmedBy,
            );
            $this->recorder->finish($run, 'succeeded', 'Submission confirmed by user.');

            return $proposal->refresh();
        });
    }
}
