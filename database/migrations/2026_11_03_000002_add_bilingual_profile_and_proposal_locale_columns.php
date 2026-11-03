<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('freelancer_profiles', function (Blueprint $table): void {
            $table->string('display_name_fa', 120)->nullable();
            $table->string('display_name_en', 120)->nullable();
            $table->string('professional_title_fa', 160)->nullable();
            $table->string('professional_title_en', 160)->nullable();
            $table->text('bio_fa')->nullable();
            $table->text('bio_en')->nullable();
            $table->string('location_fa', 120)->nullable();
            $table->string('location_en', 120)->nullable();
            $table->json('languages_fa')->nullable();
            $table->json('languages_en')->nullable();
            $table->json('services_fa')->nullable();
            $table->json('services_en')->nullable();
            $table->json('technologies_fa')->nullable();
            $table->json('technologies_en')->nullable();
        });

        Schema::table('proposal_versions', function (Blueprint $table): void {
            $table->string('content_locale', 2)->nullable();
        });

        DB::table('freelancer_profiles')->update([
            'display_name_en' => DB::raw('display_name'),
            'professional_title_en' => DB::raw('professional_title'),
            'bio_en' => DB::raw('bio'),
            'location_en' => DB::raw('location'),
            'languages_en' => DB::raw('languages'),
            'services_en' => DB::raw('services'),
            'technologies_en' => DB::raw('technologies'),
        ]);
    }

    public function down(): void
    {
        Schema::table('proposal_versions', function (Blueprint $table): void {
            $table->dropColumn('content_locale');
        });

        Schema::table('freelancer_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'display_name_fa',
                'display_name_en',
                'professional_title_fa',
                'professional_title_en',
                'bio_fa',
                'bio_en',
                'location_fa',
                'location_en',
                'languages_fa',
                'languages_en',
                'services_fa',
                'services_en',
                'technologies_fa',
                'technologies_en',
            ]);
        });
    }
};
