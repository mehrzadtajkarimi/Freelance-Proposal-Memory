<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rule_versions', function (Blueprint $table): void {
            $table->string('title')->nullable();
            $table->unsignedSmallInteger('priority')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('rule_versions', function (Blueprint $table): void {
            $table->dropColumn(['title', 'priority']);
        });
    }
};
