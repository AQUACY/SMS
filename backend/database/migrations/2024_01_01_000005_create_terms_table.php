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
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->string('name', 50);
            $table->tinyInteger('term_number'); // 1, 2, or 3
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'closing', 'closed', 'archived'])->default('draft');
            $table->integer('grace_period_days')->default(7);
            $table->date('grace_period_end')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['academic_year_id']);
            $table->index(['status']);
            $table->index(['academic_year_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};

