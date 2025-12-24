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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('class_subject_id')->constrained('class_subjects')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->string('name', 255);
            $table->enum('type', ['exam', 'quiz', 'assignment', 'project', 'other']);
            $table->decimal('total_marks', 5, 2);
            $table->decimal('weight', 5, 2); // Percentage weight in final grade
            $table->date('assessment_date');
            $table->date('due_date')->nullable();
            $table->boolean('is_finalized')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['term_id']);
            $table->index(['class_subject_id']);
            $table->index(['teacher_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};

