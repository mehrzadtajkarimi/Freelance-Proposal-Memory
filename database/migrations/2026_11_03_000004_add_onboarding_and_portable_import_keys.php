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
            $table->timestamp('setup_completed_at')->nullable();
        });

        DB::table('freelancer_profiles')->whereNotNull('id')->update([
            'setup_completed_at' => now(),
        ]);

        Schema::table('freelancer_experiences', function (Blueprint $table): void {
            $table->string('import_key', 64)->nullable();
            $table->unique(['freelancer_profile_id', 'import_key'], 'freelancer_experiences_profile_import_unique');
        });
    }

    public function down(): void
    {
        Schema::table('freelancer_experiences', function (Blueprint $table): void {
            $table->dropUnique('freelancer_experiences_profile_import_unique');
            $table->dropColumn('import_key');
        });

        Schema::table('freelancer_profiles', function (Blueprint $table): void {
            $table->dropColumn('setup_completed_at');
        });
    }
};
