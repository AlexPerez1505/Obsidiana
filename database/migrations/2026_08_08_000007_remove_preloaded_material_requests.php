<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('material_requests')) {
            return;
        }

        DB::table('material_requests')
            ->where('metadata->source', 'admin_materiales_seed')
            ->orWhere('metadata', 'like', '%admin_materiales_seed%')
            ->delete();
    }

    public function down(): void
    {
        //
    }
};
