<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_profiles', function (Blueprint $table): void {
            $table->id()->comment('Primary key.');
            $table->string('profile_key', 32)->unique()->default('primary')->comment('Stable identifier for the freelancer profile.');
            $table->string('display_name', 120)->nullable()->comment('Display name in the default language.');
            $table->string('professional_title', 160)->nullable()->comment('Professional title in the default language.');
            $table->text('bio')->nullable()->comment('Profile biography in the default language.');
            $table->unsignedTinyInteger('experience_years')->nullable()->comment('Total years of professional experience.');
            $table->string('location', 120)->nullable()->comment('Profile location in the default language.');
            $table->json('languages')->nullable()->comment('Languages in the default language format.');
            $table->json('services')->nullable()->comment('Services offered in the default language format.');
            $table->json('technologies')->nullable()->comment('Technologies in the default language format.');
            $table->string('portfolio_url', 2048)->nullable()->comment('URL of the freelancer portfolio.');
            $table->string('website_url', 2048)->nullable()->comment('URL of the freelancer website.');
            $table->timestamp('created_at')->nullable()->comment('Time the record was created.');
            $table->timestamp('updated_at')->nullable()->comment('Time the record was last updated.');
            $table->string('display_name_fa', 120)->nullable()->comment('Display name in Persian.');
            $table->string('display_name_en', 120)->nullable()->comment('Display name in English.');
            $table->string('professional_title_fa', 160)->nullable()->comment('Professional title in Persian.');
            $table->string('professional_title_en', 160)->nullable()->comment('Professional title in English.');
            $table->text('bio_fa')->nullable()->comment('Profile biography in Persian.');
            $table->text('bio_en')->nullable()->comment('Profile biography in English.');
            $table->string('location_fa', 120)->nullable()->comment('Profile location in Persian.');
            $table->string('location_en', 120)->nullable()->comment('Profile location in English.');
            $table->json('languages_fa')->nullable()->comment('Languages spoken, with Persian labels.');
            $table->json('languages_en')->nullable()->comment('Languages spoken, with English labels.');
            $table->json('services_fa')->nullable()->comment('Services offered, with Persian labels.');
            $table->json('services_en')->nullable()->comment('Services offered, with English labels.');
            $table->json('technologies_fa')->nullable()->comment('Technologies, with Persian labels.');
            $table->json('technologies_en')->nullable()->comment('Technologies, with English labels.');
            $table->timestamp('setup_completed_at')->nullable()->comment('Time profile setup was completed.');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_profiles');
    }
};
