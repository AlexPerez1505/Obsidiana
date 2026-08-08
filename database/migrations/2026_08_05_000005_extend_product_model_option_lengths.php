<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE product_options MODIFY value VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE products MODIFY name VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE products MODIFY model VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE product_options MODIFY value VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE products MODIFY name VARCHAR(150) NOT NULL');
        DB::statement('ALTER TABLE products MODIFY model VARCHAR(100) NULL');
    }
};
