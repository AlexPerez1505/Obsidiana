<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidencias_pago', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('pago_id');
            $table->foreign('pago_id')->references('id')->on('pagos')->onDelete('cascade');

            $table->string('nombre');
            $table->string('archivo_path');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evidencias_pago');
    }
};
