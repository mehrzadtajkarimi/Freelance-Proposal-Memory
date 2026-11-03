<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('profile_key', 32)->unique()->default('primary');
            $table->string('display_name', 120)->nullable();
            $table->string('professional_title', 160)->nullable();
            $table->text('bio')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->string('location', 120)->nullable();
            $table->json('languages')->nullable();
            $table->json('services')->nullable();
            $table->json('technologies')->nullable();
            $table->string('portfolio_url', 2048)->nullable();
            $table->string('website_url', 2048)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancer_profiles');
    }
};
