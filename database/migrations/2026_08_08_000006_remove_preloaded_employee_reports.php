<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_reports')) {
            return;
        }

        DB::table('employee_reports')
            ->where('metadata->source', 'admin_reportes_seed')
            ->orWhere('metadata', 'like', '%admin_reportes_seed%')
            ->delete();
    }

    public function down(): void
    {
        //
    }
};
