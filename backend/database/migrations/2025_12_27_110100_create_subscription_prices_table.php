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
        Schema::create('subscription_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            $table->string('name'); // e.g., "Standard Subscription", "Premium Subscription"
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GHS');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraint: school_id null for global, or specific school_id
            $table->unique(['school_id', 'name'], 'subscription_prices_unique_constraint');
            $table->index(['school_id']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_prices');
    }
};

