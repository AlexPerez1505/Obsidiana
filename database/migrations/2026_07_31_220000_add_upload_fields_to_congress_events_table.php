<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('congress_events', function (Blueprint $table) {
            $table->boolean('upload_access')->default(false)->after('download_text');
            $table->text('upload_text')->nullable()->after('upload_access');
        });
    }

    public function down(): void
    {
        Schema::table('congress_events', function (Blueprint $table) {
            $table->dropColumn(['upload_access', 'upload_text']);
        });
    }
};
