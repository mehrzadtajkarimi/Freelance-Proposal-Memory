<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEXES = [
        'project_requests' => ['title', 'request_body'],
        'legacy_documents' => ['body'],
        'proposal_versions' => ['body'],
        'conversation_messages' => ['body'],
        'rules' => ['title'],
        'rule_versions' => ['body'],
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (self::INDEXES as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns): void {
                $table->fullText($columns, $tableName.'_memory_search_fulltext');
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (array_reverse(array_keys(self::INDEXES)) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                $table->dropFullText($tableName.'_memory_search_fulltext');
            });
        }
    }
};
