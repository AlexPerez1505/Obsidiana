<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_maintenances', function (Blueprint $table) {
            $table->foreignId('internal_technician_id')->nullable()->after('tecnico_externo_id')->constrained('users')->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->nullable()->after('anticipo');
            $table->decimal('descuento', 12, 2)->nullable()->after('subtotal');
            $table->decimal('total', 12, 2)->nullable()->after('descuento');
        });
    }

    public function down(): void
    {
        Schema::table('service_maintenances', function (Blueprint $table) {
            $table->dropForeign(['internal_technician_id']);
            $table->dropColumn(['internal_technician_id', 'subtotal', 'descuento', 'total']);
        });
    }
};
