<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('viatics', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('place');
            $table->date('end_date')->nullable()->after('start_date');
            $table->string('state', 100)->nullable()->after('end_date');
            $table->unsignedInteger('external_companions')->default(0)->after('state');
            $table->boolean('money_given')->default(false)->after('external_companions');
            $table->decimal('fixed_fund', 12, 2)->default(0)->after('money_given');
            $table->foreignId('shortfall_payer_user_id')->nullable()->after('fixed_fund')->constrained('users')->nullOnDelete();
        });

        Schema::create('viatic_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viatic_id')->constrained('viatics')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['viatic_id', 'user_id']);
        });

        Schema::create('viatic_money_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viatic_id')->constrained('viatics')->cascadeOnDelete();
            $table->string('source', 100);
            $table->foreignId('recipient_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viatic_money_deliveries');
        Schema::dropIfExists('viatic_participants');

        Schema::table('viatics', function (Blueprint $table) {
            $table->dropForeign(['shortfall_payer_user_id']);
            $table->dropColumn([
                'start_date', 'end_date', 'state', 'external_companions',
                'money_given', 'fixed_fund', 'shortfall_payer_user_id',
            ]);
        });
    }
};
