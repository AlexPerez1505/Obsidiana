<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refacciones', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->nullable()->after('stock');
            $table->string('photo')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('refacciones', function (Blueprint $table) {
            $table->dropColumn(['price', 'photo']);
        });
    }
};
