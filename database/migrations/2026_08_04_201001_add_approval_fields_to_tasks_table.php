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
            if (!Schema::hasColumn('tasks', 'delivery_link')) {
                $table->string('delivery_link')->nullable()->after('description');
            }
            if (!Schema::hasColumn('tasks', 'review_date')) {
                $table->date('review_date')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('tasks', 'linked_piece')) {
                $table->string('linked_piece')->nullable()->after('tags');
            }
            if (!Schema::hasColumn('tasks', 'rejection_comment')) {
                $table->text('rejection_comment')->nullable()->after('linked_piece');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'rejection_comment')) {
                $table->dropColumn('rejection_comment');
            }
            if (Schema::hasColumn('tasks', 'linked_piece')) {
                $table->dropColumn('linked_piece');
            }
            if (Schema::hasColumn('tasks', 'review_date')) {
                $table->dropColumn('review_date');
            }
            if (Schema::hasColumn('tasks', 'delivery_link')) {
                $table->dropColumn('delivery_link');
            }
        });
    }
};
