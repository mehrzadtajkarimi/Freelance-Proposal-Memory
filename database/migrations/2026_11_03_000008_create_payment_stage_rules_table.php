<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_stage_rules', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->string('name_fa', 160)->comment('Rule name in Persian.');
            $table->string('name_en', 160)->comment('Rule name in English.');
            $table->unsignedBigInteger('minimum_amount_toman')->nullable()->comment('Minimum payment amount in Iranian toman.');
            $table->unsignedBigInteger('maximum_amount_toman')->nullable()->comment('Maximum payment amount in Iranian toman.');
            $table->json('stages')->comment('Ordered payment stages and their allocation rules.');
            $table->timestamp('created_at')->nullable()->comment('Time the record was created.');
            $table->timestamp('updated_at')->nullable()->comment('Time the record was last updated.');
            $table->string('status', 24)->default('approved')->index()->comment('Approval status of the payment stage rule.');
            $table->timestamp('approved_at')->nullable()->comment('Time the rule was approved.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_stage_rules');
    }
};
