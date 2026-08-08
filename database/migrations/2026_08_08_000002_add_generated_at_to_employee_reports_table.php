<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_reports', 'generated_at')) {
                $table->timestamp('generated_at')->nullable()->after('metadata');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_reports', function (Blueprint $table) {
            if (Schema::hasColumn('employee_reports', 'generated_at')) {
                $table->dropColumn('generated_at');
            }
        });
    }
};
