<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportsFullText = DB::connection()->getDriverName() === 'mysql';

        Schema::create('legacy_documents', function (Blueprint $table) use ($supportsFullText): void {
            $table->id()->comment('Primary key.');
            $table->string('source_path')->unique()->comment('Path of the imported source document.');
            $table->string('document_type', 40)->index()->comment('Type of the source document.');
            $table->string('title')->comment('Document title.');
            $table->longText('body')->comment('Full text content of the document.');
            $table->string('sha256', 64)->comment('SHA-256 checksum of the source document.');
            $table->timestamp('imported_at')->nullable()->comment('Time the document was imported.');
            $table->timestamp('created_at')->nullable()->comment('Time the record was created.');
            $table->timestamp('updated_at')->nullable()->comment('Time the record was last updated.');

            if ($supportsFullText) {
                $table->fullText(['body'], 'legacy_documents_memory_search_fulltext');
            }
        });

        Schema::create('project_requests', function (Blueprint $table) use ($supportsFullText): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('legacy_document_id')->nullable()->unique()->comment('Linked imported source document, if available.')->constrained('legacy_documents')->nullOnDelete();
            $table->string('platform', 80)->default('other')->comment('Freelance platform where the request was posted.');
            $table->string('external_id')->nullable()->comment('Request identifier assigned by the source platform.');
            $table->string('title')->index()->comment('Request title.');
            $table->longText('request_body')->comment('Full text of the client request.');
            $table->string('status', 32)->default('draft')->index()->comment('Current request workflow status.');
            $table->unsignedBigInteger('budget_min_toman')->nullable()->comment('Minimum advertised budget in Iranian toman.');
            $table->unsignedBigInteger('budget_max_toman')->nullable()->comment('Maximum advertised budget in Iranian toman.');
            $table->decimal('budget_min_amount', 20, 4)->nullable()->comment('Minimum advertised budget in the stated currency.');
            $table->decimal('budget_max_amount', 20, 4)->nullable()->comment('Maximum advertised budget in the stated currency.');
            $table->char('currency', 3)->nullable()->comment('Currency code for the advertised budget.');
            $table->unsignedSmallInteger('client_deadline_days')->nullable()->comment('Client-advertised deadline in days.');
            $table->text('source_url')->nullable()->comment('URL of the original request.');
            $table->string('client_location_text', 160)->nullable()->comment('Client location as displayed by the source.');
            $table->string('client_city', 100)->nullable()->comment('Client city, when provided.');
            $table->string('client_region', 100)->nullable()->comment('Client region or state, when provided.');
            $table->string('client_country', 100)->nullable()->comment('Client country, when provided.');
            $table->string('client_deadline_text', 160)->nullable()->comment('Client deadline text as displayed by the source.');
            $table->timestamp('posted_at')->nullable()->index()->comment('Request posting time, when known.');
            $table->string('posted_time_text', 120)->nullable()->comment('Posting-time label as displayed by the source.');
            $table->string('posted_at_basis', 24)->default('unknown')->comment('Basis used to determine the posting timestamp.');
            $table->timestamp('captured_at')->nullable()->comment('Time the source listing was captured.');
            $table->string('source_timezone', 64)->nullable()->comment('Timezone used to interpret source dates.');
            $table->boolean('client_is_new')->nullable()->comment('Whether the source identifies the client as new.');
            $table->unsignedInteger('client_reviews_count')->nullable()->comment('Client review count shown by the source.');
            $table->decimal('client_rating', 6, 3)->nullable()->comment('Client rating shown by the source.');
            $table->decimal('client_rating_scale', 6, 3)->nullable()->comment('Maximum value of the displayed client rating scale.');
            $table->boolean('client_payment_verified')->nullable()->comment('Whether client payment verification is shown by the source.');
            $table->unsignedInteger('applicants_count')->nullable()->comment('Applicant count shown by the source.');
            $table->boolean('is_featured')->nullable()->comment('Whether the request is marked as featured.');
            $table->boolean('is_full_time_known')->default(false)->comment('Whether full-time status was explicitly reported.');
            $table->boolean('is_full_time')->nullable()->default(null)->comment('Whether the request is for full-time work.');
            $table->json('metadata')->nullable()->comment('Additional source-specific request data.');
            $table->timestamp('created_at')->nullable()->comment('Time the record was created.');
            $table->timestamp('updated_at')->nullable()->comment('Time the record was last updated.');

            $table->unique(['platform', 'external_id']);

            if ($supportsFullText) {
                $table->fullText(['title', 'request_body'], 'project_requests_memory_search_fulltext');
            }
        });

        Schema::create('request_emphases', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('project_request_id')->comment('Request containing this emphasized requirement.')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1)->comment('Display order of the requirement.');
            $table->string('title', 255)->comment('Short title for the emphasized requirement.');
            $table->longText('source_excerpt')->nullable()->comment('Supporting excerpt copied from the client request.');
            $table->longText('interpretation')->comment('Interpretation of the emphasized requirement.');
            $table->timestamp('created_at')->nullable()->comment('Time the record was created.');
            $table->timestamp('updated_at')->nullable()->comment('Time the record was last updated.');

            $table->index(['project_request_id', 'position']);
        });

        Schema::create('proposal_versions', function (Blueprint $table) use ($supportsFullText): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('project_request_id')->comment('Request this proposal version belongs to.')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->comment('Version number within the request.');
            $table->string('status', 24)->default('draft')->index()->comment('Proposal state, such as draft or submitted.');
            $table->longText('body')->comment('Client-facing proposal text.');
            $table->text('key_idea')->nullable()->comment('Concise request-specific idea included with the proposal.');
            $table->unsignedBigInteger('price_toman')->nullable()->comment('Proposed price in Iranian toman.');
            $table->decimal('price_amount', 20, 4)->nullable()->comment('Proposed price in the stated currency.');
            $table->char('currency', 3)->nullable()->comment('Currency code for the proposed price.');
            $table->unsignedSmallInteger('delivery_days')->nullable()->comment('Proposed delivery duration in days.');
            $table->string('delivery_unit', 24)->default('calendar_day')->comment('Unit used for the proposed delivery duration.');
            $table->string('content_locale', 2)->nullable()->comment('Language code of the proposal text.');
            $table->json('payment_terms')->nullable()->comment('Payment milestones or terms proposed for the work.');
            $table->json('estimate')->nullable()->comment('Private estimate details for the proposal.');
            $table->longText('assumptions')->nullable()->comment('Assumptions used to prepare the proposal.');
            $table->timestamp('submitted_at')->nullable()->comment('Time the proposal was confirmed as submitted.');
            $table->timestamp('created_at')->useCurrent()->comment('Time this proposal version was created.');

            $table->unique(['project_request_id', 'version']);
            $table->index(['project_request_id', 'status']);

            if ($supportsFullText) {
                $table->fullText(['body'], 'proposal_versions_memory_search_fulltext');
            }
        });

        Schema::create('conversation_messages', function (Blueprint $table) use ($supportsFullText): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('project_request_id')->comment('Request associated with this message.')->constrained()->cascadeOnDelete();
            $table->string('direction', 12)->index()->comment('Whether the message was incoming or outgoing.');
            $table->longText('body')->comment('Message text.');
            $table->timestamp('occurred_at')->nullable()->index()->comment('Time the message exchange occurred.');
            $table->json('metadata')->nullable()->comment('Additional message source data.');
            $table->timestamp('created_at')->nullable()->comment('Time the record was created.');
            $table->timestamp('updated_at')->nullable()->comment('Time the record was last updated.');

            if ($supportsFullText) {
                $table->fullText(['body'], 'conversation_messages_memory_search_fulltext');
            }
        });

        Schema::create('rules', function (Blueprint $table) use ($supportsFullText): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('project_request_id')->nullable()->comment('Request this rule applies to, if request-scoped.')->constrained()->nullOnDelete();
            $table->string('legacy_key', 64)->nullable()->unique()->comment('Legacy identifier retained for imported rules.');
            $table->string('scope', 16)->default('global')->index()->comment('Whether the rule is global or request-specific.');
            $table->string('title')->comment('Short rule title.');
            $table->string('status', 24)->default('pending_approval')->index()->comment('Current approval status of the rule.');
            $table->unsignedSmallInteger('current_version')->default(1)->comment('Current rule version number.');
            $table->unsignedSmallInteger('priority')->default(100)->comment('Ordering priority for the rule.');
            $table->timestamp('approved_at')->nullable()->comment('Time the current rule was approved.');
            $table->timestamp('created_at')->nullable()->comment('Time the record was created.');
            $table->timestamp('updated_at')->nullable()->comment('Time the record was last updated.');

            $table->index(['scope', 'status', 'priority']);

            if ($supportsFullText) {
                $table->fullText(['title'], 'rules_memory_search_fulltext');
            }
        });

        Schema::create('rule_versions', function (Blueprint $table) use ($supportsFullText): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('rule_id')->comment('Rule this version belongs to.')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->comment('Version number within the rule.');
            $table->longText('body')->comment('Full text of this rule version.');
            $table->string('status', 24)->default('pending_approval')->index()->comment('Approval status of this rule version.');
            $table->string('source_type', 40)->nullable()->comment('Origin type of the rule version.');
            $table->string('source_reference')->nullable()->comment('Reference to the source of this rule version.');
            $table->text('change_summary')->nullable()->comment('Summary of changes from the prior version.');
            $table->string('title')->nullable()->comment('Title recorded for this rule version.');
            $table->unsignedSmallInteger('priority')->nullable()->comment('Priority recorded for this rule version.');
            $table->timestamp('approved_at')->nullable()->comment('Time this rule version was approved.');
            $table->timestamp('created_at')->useCurrent()->comment('Time this rule version was created.');

            $table->unique(['rule_id', 'version']);

            if ($supportsFullText) {
                $table->fullText(['body'], 'rule_versions_memory_search_fulltext');
            }
        });

        Schema::create('workflow_runs', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->uuid('run_key')->unique()->comment('Unique identifier for this workflow run.');
            $table->foreignId('project_request_id')->nullable()->comment('Request involved in the run, if any.')->constrained()->nullOnDelete();
            $table->foreignId('proposal_version_id')->nullable()->comment('Proposal version involved in the run, if any.')->constrained()->nullOnDelete();
            $table->string('run_type', 40)->index()->comment('Kind of workflow operation performed.');
            $table->string('status', 24)->index()->comment('Outcome state of the workflow run.');
            $table->string('source_environment', 24)->default('local')->index()->comment('Environment that initiated the run.');
            $table->string('actor', 80)->nullable()->comment('User or process that initiated the run.');
            $table->timestamp('started_at')->index()->comment('Time the workflow run started.');
            $table->timestamp('finished_at')->nullable()->comment('Time the workflow run finished.');
            $table->text('result_summary')->nullable()->comment('Short summary of the workflow result.');
            $table->json('context')->nullable()->comment('Context data captured for the workflow run.');
            $table->timestamp('created_at')->nullable()->comment('Time the record was created.');
            $table->timestamp('updated_at')->nullable()->comment('Time the record was last updated.');
        });

        Schema::create('workflow_events', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('workflow_run_id')->nullable()->comment('Workflow run this event belongs to, if any.')->constrained()->nullOnDelete();
            $table->foreignId('project_request_id')->nullable()->comment('Request associated with this event, if any.')->constrained()->nullOnDelete();
            $table->string('event_type', 48)->index()->comment('Type of workflow event.');
            $table->string('status', 24)->nullable()->index()->comment('Status recorded for this event.');
            $table->string('source_environment', 24)->default('local')->index()->comment('Environment where the event occurred.');
            $table->string('actor', 80)->nullable()->comment('User or process that caused the event.');
            $table->text('message')->nullable()->comment('Human-readable event details.');
            $table->json('context')->nullable()->comment('Structured details captured for the event.');
            $table->timestamp('occurred_at')->index()->comment('Time the event occurred.');
            $table->timestamp('created_at')->nullable()->comment('Time the record was created.');
            $table->timestamp('updated_at')->nullable()->comment('Time the record was last updated.');
        });

        Schema::create('workflow_run_rule_versions', function (Blueprint $table): void {
            $table->foreignId('workflow_run_id')->comment('Workflow run that used the rule version.')->constrained()->cascadeOnDelete();
            $table->foreignId('rule_version_id')->comment('Rule version used by the workflow run.')->constrained()->restrictOnDelete();
            $table->primary(['workflow_run_id', 'rule_version_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_run_rule_versions');
        Schema::dropIfExists('workflow_events');
        Schema::dropIfExists('workflow_runs');
        Schema::dropIfExists('rule_versions');
        Schema::dropIfExists('rules');
        Schema::dropIfExists('conversation_messages');
        Schema::dropIfExists('request_emphases');
        Schema::dropIfExists('proposal_versions');
        Schema::dropIfExists('project_requests');
        Schema::dropIfExists('legacy_documents');
    }
};
