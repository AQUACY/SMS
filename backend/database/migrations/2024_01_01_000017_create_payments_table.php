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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GHS');
            $table->enum('payment_method', ['momo', 'bank', 'cash', 'other'])->default('momo');
            $table->string('momo_provider', 50)->nullable(); // 'mtn', 'vodafone', 'airteltigo'
            $table->string('momo_transaction_id', 100)->nullable();
            $table->string('reference', 100)->unique();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->json('webhook_data')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('verified_by', 50)->nullable(); // 'webhook', 'admin', 'manual'
            $table->timestamps();
            
            $table->index(['parent_id']);
            $table->index(['student_id']);
            $table->index(['term_id']);
            $table->index(['status']);
            $table->index(['reference']);
            $table->index(['momo_transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

