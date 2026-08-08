<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_option_relations', function (Blueprint $table) {
            $table->id();
            $table->string('parent_type', 40);
            $table->string('parent_value', 255);
            $table->string('child_type', 40);
            $table->string('child_value', 255);
            $table->timestamps();

            $table->unique(
                ['parent_type', 'parent_value', 'child_type', 'child_value'],
                'product_option_relations_unique'
            );
            $table->index(['parent_type', 'parent_value'], 'product_option_relations_parent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_option_relations');
    }
};
