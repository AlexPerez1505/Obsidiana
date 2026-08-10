<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('brand_guides', 'guias_de_marca');
        Schema::rename('categories', 'categorias');
        Schema::rename('congress_events', 'eventos_congreso');
        Schema::rename('congress_event_user', 'evento_congreso_usuario');
        Schema::rename('tasks', 'tareas');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('tareas', 'tasks');
        Schema::rename('evento_congreso_usuario', 'congress_event_user');
        Schema::rename('eventos_congreso', 'congress_events');
        Schema::rename('categorias', 'categories');
        Schema::rename('guias_de_marca', 'brand_guides');
    }
};
