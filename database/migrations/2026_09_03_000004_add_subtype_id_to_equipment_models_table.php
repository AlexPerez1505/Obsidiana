<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('equipment_models')) {
            return;
        }

        if (! Schema::hasColumn('equipment_models', 'subtype_id')) {
            Schema::table('equipment_models', function (Blueprint $table) {
                $table->foreignId('subtype_id')
                    ->nullable()
                    ->after('brand_id')
                    ->constrained('subtypes')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('equipment_models') || ! Schema::hasColumn('equipment_models', 'subtype_id')) {
            return;
        }

        Schema::table('equipment_models', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subtype_id');
        });
    }
};
