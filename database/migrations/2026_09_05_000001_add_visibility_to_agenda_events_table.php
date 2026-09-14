<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            if (! Schema::hasColumn('agenda_events', 'visibility')) {
                $table->string('visibility', 30)->default('publico')->after('status');
            }

            if (! Schema::hasColumn('agenda_events', 'reason')) {
                $table->string('reason')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('agenda_events', 'duration_minutes')) {
                $table->unsignedSmallInteger('duration_minutes')->default(60)->after('start_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('agenda_events', function (Blueprint $table) {
            if (Schema::hasColumn('agenda_events', 'duration_minutes')) {
                $table->dropColumn('duration_minutes');
            }

            if (Schema::hasColumn('agenda_events', 'reason')) {
                $table->dropColumn('reason');
            }

            if (Schema::hasColumn('agenda_events', 'visibility')) {
                $table->dropColumn('visibility');
            }
        });
    }
};
