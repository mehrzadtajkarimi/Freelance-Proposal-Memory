<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('source_path')->unique();
            $table->string('document_type', 40)->index();
            $table->string('title');
            $table->longText('body');
            $table->string('sha256', 64);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('legacy_document_id')->nullable()->unique()->constrained('legacy_documents')->nullOnDelete();
            $table->string('platform', 80)->default('other');
            $table->string('external_id')->nullable();
            $table->string('title')->index();
            $table->longText('request_body');
            $table->string('status', 32)->default('draft')->index();
            $table->decimal('budget_min_amount', 20, 4)->nullable();
            $table->decimal('budget_max_amount', 20, 4)->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedSmallInteger('client_deadline_days')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'external_id']);
        });

        Schema::create('proposal_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('status', 24)->default('draft')->index();
            $table->longText('body');
            $table->decimal('price_amount', 20, 4)->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedSmallInteger('delivery_days')->nullable();
            $table->string('delivery_unit', 24)->default('calendar_day');
            $table->json('payment_terms')->nullable();
            $table->json('estimate')->nullable();
            $table->longText('assumptions')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['project_request_id', 'version']);
            $table->index(['project_request_id', 'status']);
        });

        Schema::create('conversation_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_request_id')->constrained()->cascadeOnDelete();
            $table->string('direction', 12)->index();
            $table->longText('body');
            $table->timestamp('occurred_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('legacy_key', 64)->nullable()->unique();
            $table->string('scope', 16)->default('global')->index();
            $table->string('title');
            $table->string('status', 24)->default('pending_approval')->index();
            $table->unsignedSmallInteger('current_version')->default(1);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['scope', 'status', 'priority']);
        });

        Schema::create('rule_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rule_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->longText('body');
            $table->string('status', 24)->default('pending_approval')->index();
            $table->string('source_type', 40)->nullable();
            $table->string('source_reference')->nullable();
            $table->text('change_summary')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['rule_id', 'version']);
        });

        Schema::create('workflow_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('run_key')->unique();
            $table->foreignId('project_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('proposal_version_id')->nullable()->constrained()->nullOnDelete();
            $table->string('run_type', 40)->index();
            $table->string('status', 24)->index();
            $table->string('source_environment', 24)->default('local')->index();
            $table->string('actor', 80)->nullable();
            $table->timestamp('started_at')->index();
            $table->timestamp('finished_at')->nullable();
            $table->text('result_summary')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_run_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 48)->index();
            $table->string('status', 24)->nullable()->index();
            $table->string('source_environment', 24)->default('local')->index();
            $table->string('actor', 80)->nullable();
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('workflow_run_rule_versions', function (Blueprint $table): void {
            $table->foreignId('workflow_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rule_version_id')->constrained()->restrictOnDelete();
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
        Schema::dropIfExists('proposal_versions');
        Schema::dropIfExists('project_requests');
        Schema::dropIfExists('legacy_documents');
    }
};
