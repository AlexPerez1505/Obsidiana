<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('tasks', 'created_by')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete()->after('tags');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'created_by')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
