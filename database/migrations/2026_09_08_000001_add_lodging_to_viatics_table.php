<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viatics', function (Blueprint $table) {
            $table->decimal('lodging', 12, 2)->nullable()->default(0)->after('meals');
        });
    }

    public function down(): void
    {
        Schema::table('viatics', function (Blueprint $table) {
            $table->dropColumn('lodging');
        });
    }
};
