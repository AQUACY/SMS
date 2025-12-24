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
        Schema::create('grading_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Standard Grading", "Ghana Education Service"
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false); // Only one default per school
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['school_id', 'is_default']);
            $table->index(['school_id', 'is_active']);
        });

        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_scale_id')->constrained()->onDelete('cascade');
            $table->string('grade', 10); // A, B, C, D, E, F, etc.
            $table->string('label')->nullable(); // e.g., "Excellent", "Very Good"
            $table->decimal('min_percentage', 5, 2); // Minimum percentage for this grade (e.g., 80.00)
            $table->decimal('max_percentage', 5, 2)->nullable(); // Maximum percentage (usually null for highest grade)
            $table->string('gpa_value', 10)->nullable(); // GPA value if applicable (e.g., "4.0", "3.5")
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // Order for display (higher = better grade)
            $table->timestamps();
            
            $table->unique(['grading_scale_id', 'grade']);
            $table->index(['grading_scale_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade_levels');
        Schema::dropIfExists('grading_scales');
    }
};

