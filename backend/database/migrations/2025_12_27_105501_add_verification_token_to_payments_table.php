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
            $table->string('verification_token', 100)->unique()->nullable()->after('reference');
            $table->string('payment_reference', 100)->nullable()->after('verification_token'); // Reference from parent's payment
            $table->index(['verification_token']);
            $table->index(['payment_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_reference']);
            $table->dropIndex(['verification_token']);
            $table->dropColumn(['verification_token', 'payment_reference']);
        });
    }
};
