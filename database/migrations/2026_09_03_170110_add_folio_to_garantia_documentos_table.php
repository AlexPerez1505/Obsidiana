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
        if (! Schema::hasColumn('garantia_documentos', 'folio')) {
            Schema::table('garantia_documentos', function (Blueprint $table) {
                $table->string('folio')->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('garantia_documentos', 'folio')) {
            Schema::table('garantia_documentos', function (Blueprint $table) {
                $table->dropColumn('folio');
            });
        }
    }
};
