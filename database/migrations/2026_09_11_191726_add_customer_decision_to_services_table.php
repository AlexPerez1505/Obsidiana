<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('customer_decision')->nullable()->after('status');
            $table->timestamp('customer_decision_at')->nullable()->after('customer_decision');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['customer_decision', 'customer_decision_at']);
        });
    }
};
