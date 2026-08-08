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
        Schema::table('tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('tasks', 'category')) {
                $table->string('category')->nullable()->after('title');
            }
            if (!Schema::hasColumn('tasks', 'reviewer_id')) {
                $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete()->after('user_id');
            }
            if (!Schema::hasColumn('tasks', 'platform')) {
                $table->json('platform')->nullable()->after('reviewer_id');
            }
            if (!Schema::hasColumn('tasks', 'has_video')) {
                $table->boolean('has_video')->default(false)->after('platform');
            }
            if (!Schema::hasColumn('tasks', 'task_description')) {
                $table->text('task_description')->nullable()->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'task_description')) {
                $table->dropColumn('task_description');
            }
            if (Schema::hasColumn('tasks', 'has_video')) {
                $table->dropColumn('has_video');
            }
            if (Schema::hasColumn('tasks', 'platform')) {
                $table->dropColumn('platform');
            }
            if (Schema::hasColumn('tasks', 'reviewer_id')) {
                $table->dropConstrainedForeignId('reviewer_id');
            }
            if (Schema::hasColumn('tasks', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
