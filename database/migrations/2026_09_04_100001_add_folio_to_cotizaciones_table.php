<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cotizaciones', 'folio')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->string('folio')->unique()->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cotizaciones', 'folio')) {
            Schema::table('cotizaciones', function (Blueprint $table) {
                $table->dropUnique(['folio']);
                $table->dropColumn('folio');
            });
        }
    }
};
