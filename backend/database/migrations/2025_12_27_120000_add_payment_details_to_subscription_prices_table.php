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
        Schema::table('subscription_prices', function (Blueprint $table) {
            $table->string('payment_number')->nullable()->after('currency');
            $table->enum('payment_network', ['mtn', 'vodafone', 'airteltigo'])->nullable()->after('payment_number');
            $table->string('payment_name')->nullable()->after('payment_network');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_prices', function (Blueprint $table) {
            $table->dropColumn(['payment_number', 'payment_network', 'payment_name']);
        });
    }
};

