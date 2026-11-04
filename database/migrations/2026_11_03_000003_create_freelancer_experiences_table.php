<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_experiences', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->foreignId('freelancer_profile_id')->comment('Profile this experience belongs to.')->constrained()->cascadeOnDelete();
            $table->string('domain_fa', 120)->nullable()->comment('Experience domain in Persian.');
            $table->string('domain_en', 120)->nullable()->comment('Experience domain in English.');
            $table->string('position_fa', 160)->nullable()->comment('Position or role in Persian.');
            $table->string('position_en', 160)->nullable()->comment('Position or role in English.');
            $table->string('organization_fa', 180)->nullable()->comment('Organization name in Persian.');
            $table->string('organization_en', 180)->nullable()->comment('Organization name in English.');
            $table->unsignedTinyInteger('years')->nullable()->comment('Years of experience in this role or domain.');
            $table->text('description_fa')->nullable()->comment('Experience description in Persian.');
            $table->text('description_en')->nullable()->comment('Experience description in English.');
            $table->json('skills_fa')->nullable()->comment('Skills for this experience, with Persian labels.');
            $table->json('skills_en')->nullable()->comment('Skills for this experience, with English labels.');
            $table->timestamp('created_at')->nullable()->comment('Time the record was created.');
            $table->timestamp('updated_at')->nullable()->comment('Time the record was last updated.');
            $table->string('import_key', 64)->nullable()->comment('Stable key used to identify imported experience records.');

            $table->index(['freelancer_profile_id', 'id']);
            $table->unique(['freelancer_profile_id', 'import_key'], 'freelancer_experiences_profile_import_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_experiences');
    }
};
