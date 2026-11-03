<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_stage_rules')) {
            Schema::create('payment_stage_rules', function (Blueprint $table): void {
                $table->id();
                $table->string('name_fa', 160);
                $table->string('name_en', 160);
                $table->unsignedBigInteger('minimum_amount_toman')->nullable();
                $table->unsignedBigInteger('maximum_amount_toman')->nullable();
                $table->json('stages');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_stage_rules');
    }
};
