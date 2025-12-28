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
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('class_id')->nullable()->constrained('classes')->onDelete('cascade');
            $table->enum('level_category', ['nursery', 'creche', 'primary', 'jhs', 'shs'])->nullable();
            $table->string('name'); // e.g., "Term 1 Subscription Fee"
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('GHS');
            $table->boolean('is_active')->default(true);
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Unique constraint: school + term + name + (class_id or level_category or both null for school-wide)
            $table->unique(['school_id', 'term_id', 'class_id', 'level_category', 'name'], 'fees_unique_constraint');
            $table->index(['school_id']);
            $table->index(['term_id']);
            $table->index(['class_id']);
            $table->index(['level_category']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
