<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('material_requests')) {
            return;
        }

        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->unique();
            $table->string('category');
            $table->string('material_name');
            $table->unsignedInteger('quantity');
            $table->string('unit', 30)->default('Pieza');
            $table->date('required_date')->nullable();
            $table->string('urgency', 30)->default('Normal');
            $table->text('justification')->nullable();
            $table->string('status', 30)->default('borrador');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->unsignedInteger('approved_quantity')->nullable();
            $table->foreignId('delivered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'urgency']);
            $table->index('required_date');
            $table->index('requested_by');
            $table->index('reviewed_by');
            $table->index('delivered_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_requests');
    }
};
