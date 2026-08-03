<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_guides', function (Blueprint $table) {
            $table->id();
            $table->json('colors');
            $table->json('fonts');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_guides');
    }
};
