<?php

namespace App\Console\Commands;

use App\Models\ProjectRequest;
use App\Services\ContentSanitizer;
use App\Services\WorkflowRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class MemoryRecordRequestCommand extends Command
{
    protected $signature = 'memory:record-request {--stdin-json : Read a JSON object from standard input}';

    protected $description = 'Save a project request and its basic proposal metadata';

    public function handle(ContentSanitizer $sanitizer, WorkflowRecorder $recorder): int
    {
        if (! $this->option('stdin-json')) {
            $this->error('Pass --stdin-json and provide title/body as JSON on standard input.');

            return self::INVALID;
        }

        $input = json_decode(stream_get_contents(STDIN), true);
        if (! is_array($input)
            || ! is_string($input['title'] ?? null) || trim($input['title']) === ''
            || ! is_string($input['body'] ?? null) || trim($input['body']) === '') {
            $this->error('JSON must include non-empty title and body fields.');

            return self::INVALID;
        }

        $status = $input['status'] ?? 'draft';
        $budgetMin = $input['budget_min_amount'] ?? null;
        $budgetMax = $input['budget_max_amount'] ?? null;
        $currency = isset($input['currency']) && is_string($input['currency'])
            ? strtoupper(trim($input['currency']))
            : null;
        $deadlineDays = $input['client_deadline_days'] ?? null;
        $validStatuses = ['draft', 'negotiating', 'agreed', 'submitted', 'closed', 'cancelled'];
        $validOptionalAmounts = static fn (mixed $value): bool => $value === null
            || ((is_int($value) || is_float($value) || is_string($value))
                && preg_match('/^\d+(?:\.\d{1,4})?$/D', (string) $value) === 1);
        $validOptionalIntegers = static fn (mixed $value): bool => $value === null
            || ((is_int($value) || is_string($value))
                && filter_var($value, FILTER_VALIDATE_INT) !== false
                && (int) $value >= 0);

        if (! in_array($status, $validStatuses, true)
            || ! $validOptionalAmounts($budgetMin)
            || ! $validOptionalAmounts($budgetMax)
            || ! $validOptionalIntegers($deadlineDays)
            || ($deadlineDays !== null && (int) $deadlineDays < 1)
            || ($budgetMin !== null && $budgetMax !== null && (float) $budgetMin > (float) $budgetMax)
            || ($currency !== null && ! preg_match('/^[A-Z]{3}$/D', $currency))
            || (($budgetMin !== null || $budgetMax !== null) && ! preg_match('/^[A-Z]{3}$/D', $currency ?? ''))) {
            $this->error('Status and numeric request metadata are invalid.');

            return self::INVALID;
        }

        $metadata = $sanitizer->redactMetadata(is_array($input['metadata'] ?? null) ? $input['metadata'] : []);

        $request = DB::transaction(function () use ($input, $metadata, $status, $budgetMin, $budgetMax, $currency, $deadlineDays, $sanitizer, $recorder): ProjectRequest {
            $request = ProjectRequest::query()->create([
                'platform' => $input['platform'] ?? 'other',
                'external_id' => isset($input['external_id']) ? $sanitizer->redact((string) $input['external_id']) : null,
                'title' => $sanitizer->redact(trim($input['title'])),
                'request_body' => $sanitizer->redact(trim($input['body'])),
                'status' => $status,
                'budget_min_amount' => $budgetMin,
                'budget_max_amount' => $budgetMax,
                'currency' => $currency,
                'client_deadline_days' => $deadlineDays,
                'metadata' => $metadata !== [] ? $metadata : null,
            ]);

            $recorder->event(null, $request, 'request.recorded', 'completed', 'Project request saved.', actor: 'agent');

            return $request;
        });

        $this->info(json_encode(['id' => $request->id, 'title' => $request->title], JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
