<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('value', 100);
            $table->timestamps();

            $table->unique(['type', 'value']);
            $table->index('type');
        });

        DB::table('product_options')->insert([
            ['type' => 'category', 'value' => 'Endoscopia', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'value' => 'Consumibles', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'value' => 'Instrumental', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'category', 'value' => 'Refacciones', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'unit', 'value' => 'Pza', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'unit', 'value' => 'Caja', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'unit', 'value' => 'Kit', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'unit', 'value' => 'Paquete', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
