<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_experiences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('freelancer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('domain_fa', 120)->nullable();
            $table->string('domain_en', 120)->nullable();
            $table->string('position_fa', 160)->nullable();
            $table->string('position_en', 160)->nullable();
            $table->string('organization_fa', 180)->nullable();
            $table->string('organization_en', 180)->nullable();
            $table->unsignedTinyInteger('years')->nullable();
            $table->text('description_fa')->nullable();
            $table->text('description_en')->nullable();
            $table->json('skills_fa')->nullable();
            $table->json('skills_en')->nullable();
            $table->timestamps();

            $table->index(['freelancer_profile_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_experiences');
    }
};
