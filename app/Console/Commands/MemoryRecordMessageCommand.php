<?php

namespace App\Console\Commands;

use App\Models\ConversationMessage;
use App\Models\ProjectRequest;
use App\Services\ContentSanitizer;
use App\Services\WorkflowRecorder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class MemoryRecordMessageCommand extends Command
{
    protected $signature = 'memory:record-message {projectId : Saved project request ID} {direction : incoming or outgoing} {--stdin-json : Read message body and optional timestamp from standard input}';

    protected $description = 'Append a sanitized conversation message to a saved project';

    public function handle(ContentSanitizer $sanitizer, WorkflowRecorder $recorder): int
    {
        if (! $this->option('stdin-json')) {
            $this->error('Pass --stdin-json and provide a body field as JSON on standard input.');

            return self::INVALID;
        }

        $direction = (string) $this->argument('direction');
        if (! in_array($direction, ['incoming', 'outgoing'], true)) {
            $this->error('Direction must be incoming or outgoing.');

            return self::INVALID;
        }

        $input = json_decode(stream_get_contents(STDIN), true);
        if (! is_array($input) || ! is_string($input['body'] ?? null) || trim($input['body']) === '') {
            $this->error('JSON must include a non-empty body field.');

            return self::INVALID;
        }

        $request = ProjectRequest::query()->find($this->argument('projectId'));
        if (! $request) {
            $this->error('Project request was not found.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($request, $direction, $input, $sanitizer, $recorder): void {
            ConversationMessage::query()->create([
                'project_request_id' => $request->id,
                'direction' => $direction,
                'body' => $sanitizer->redact(trim($input['body'])),
                'occurred_at' => $input['occurred_at'] ?? now(),
                'metadata' => $sanitizer->redactMetadata(array_intersect_key(
                    is_array($input['metadata'] ?? null) ? $input['metadata'] : [],
                    array_flip(['platform_message_id', 'platform_status', 'source_reference']),
                )) ?: null,
            ]);

            $recorder->event(null, $request, 'message.recorded', 'completed', "A {$direction} project message was saved.", actor: 'agent');
        });

        $this->info("Message saved for project request {$request->id}.");

        return self::SUCCESS;
    }
}
