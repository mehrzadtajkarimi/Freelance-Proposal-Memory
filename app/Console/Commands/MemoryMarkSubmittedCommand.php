<?php

namespace App\Console\Commands;

use App\Actions\MarkProposalAsSubmitted;
use App\Models\ProposalVersion;
use Illuminate\Console\Command;

final class MemoryMarkSubmittedCommand extends Command
{
    protected $signature = 'memory:mark-submitted {proposalId : Proposal version ID} {confirmedBy : User who confirmed actual submission}';

    protected $description = 'Record a proposal submission only after the user confirms it was sent';

    public function handle(MarkProposalAsSubmitted $action): int
    {
        $proposal = ProposalVersion::query()->find($this->argument('proposalId'));
        if (! $proposal) {
            $this->error('Proposal version was not found.');

            return self::FAILURE;
        }

        $submitted = $action->handle($proposal, (string) $this->argument('confirmedBy'));
        $this->info(json_encode(['proposal_id' => $submitted->id, 'status' => $submitted->status, 'submitted_at' => $submitted->submitted_at], JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
