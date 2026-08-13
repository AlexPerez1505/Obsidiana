<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('is_admin', true)->update([
            'approval_pin_hash' => Hash::make('315454'),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('is_admin', true)->update([
            'approval_pin_hash' => null,
            'updated_at' => now(),
        ]);
    }
};
