<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'serial_number')) {
                $table->string('serial_number', 100)->nullable()->unique();
            }

            if (! Schema::hasColumn('products', 'price')) {
                $table->decimal('price', 12, 2)->default(0);
            }
        });

        DB::table('products')
            ->whereNull('serial_number')
            ->update(['serial_number' => DB::raw('code')]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'serial_number')) {
                $table->dropUnique(['serial_number']);
                $table->dropColumn('serial_number');
            }

            if (Schema::hasColumn('products', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
