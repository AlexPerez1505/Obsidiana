<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Tokens de la API (Sanctum) con lo que Obsidiana necesita encima:
|
|   - tipo: 'app' cuando un usuario inicia sesión desde una app, o
|     'integracion' cuando es una llave fija para otro sistema.
|   - ip_permitidas: si se llena, la llave solo funciona desde esas IP.
|   - creado_por_id: quién emitió la llave (puede ser distinto del dueño).
|   - last_used_ip: de dónde se usó por última vez.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('tipo', 20)->default('app')->index();
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->json('ip_permitidas')->nullable();
            $table->foreignId('creado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
