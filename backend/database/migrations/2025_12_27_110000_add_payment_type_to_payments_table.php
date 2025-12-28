<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_type')) {
                $table->enum('payment_type', ['fee_payment', 'subscription_payment'])->default('subscription_payment')->after('term_id');
            }
            
            if (!Schema::hasColumn('payments', 'initiated_by')) {
                $table->foreignId('initiated_by')->nullable()->after('parent_id')->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'initiated_by')) {
                $table->dropForeign(['initiated_by']);
                $table->dropColumn('initiated_by');
            }
            
            if (Schema::hasColumn('payments', 'payment_type')) {
                $table->dropColumn('payment_type');
            }
        });
    }
};

