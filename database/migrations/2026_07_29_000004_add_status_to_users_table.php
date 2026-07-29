<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // pending = esperando aprobación del admin
            // approved = con acceso
            // banned = desactivado / baneado
            $table->string('status')->default('pending')->after('is_admin');
            $table->string('banned_reason')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('banned_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'banned_reason', 'approved_at']);
        });
    }
};
