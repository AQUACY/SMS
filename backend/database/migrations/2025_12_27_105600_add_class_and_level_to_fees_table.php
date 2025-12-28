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
        Schema::table('fees', function (Blueprint $table) {
            // Add class_id column if it doesn't exist
            if (!Schema::hasColumn('fees', 'class_id')) {
                $table->foreignId('class_id')->nullable()->after('term_id')->constrained('classes')->onDelete('cascade');
            }
            
            // Add level_category column if it doesn't exist
            if (!Schema::hasColumn('fees', 'level_category')) {
                $table->enum('level_category', ['nursery', 'creche', 'primary', 'jhs', 'shs'])->nullable()->after('class_id');
            }
        });
        
        // Add indexes separately to avoid issues
        Schema::table('fees', function (Blueprint $table) {
            // Try to drop old unique constraint if it exists (might fail, that's okay)
            try {
                $table->dropUnique(['school_id', 'term_id', 'name']);
            } catch (\Exception $e) {
                // Constraint might not exist, ignore
            }
        });
        
        // Add new unique constraint
        Schema::table('fees', function (Blueprint $table) {
            try {
                $table->unique(['school_id', 'term_id', 'class_id', 'level_category', 'name'], 'fees_unique_constraint');
            } catch (\Exception $e) {
                // Constraint might already exist, ignore
            }
        });
        
        // Add indexes
        Schema::table('fees', function (Blueprint $table) {
            if (Schema::hasColumn('fees', 'class_id')) {
                try {
                    $table->index('class_id', 'fees_class_id_index');
                } catch (\Exception $e) {
                    // Index might already exist
                }
            }
            
            if (Schema::hasColumn('fees', 'level_category')) {
                try {
                    $table->index('level_category', 'fees_level_category_index');
                } catch (\Exception $e) {
                    // Index might already exist
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            // Drop new unique constraint
            try {
                $table->dropUnique('fees_unique_constraint');
            } catch (\Exception $e) {
                // Might not exist
            }
            
            // Drop indexes
            try {
                $table->dropIndex('fees_class_id_index');
            } catch (\Exception $e) {
                // Might not exist
            }
            
            try {
                $table->dropIndex('fees_level_category_index');
            } catch (\Exception $e) {
                // Might not exist
            }
            
            // Drop foreign key and column
            if (Schema::hasColumn('fees', 'class_id')) {
                try {
                    $table->dropForeign(['class_id']);
                } catch (\Exception $e) {
                    // Might not exist
                }
                $table->dropColumn('class_id');
            }
            
            if (Schema::hasColumn('fees', 'level_category')) {
                $table->dropColumn('level_category');
            }
            
            // Restore old unique constraint
            try {
                $table->unique(['school_id', 'term_id', 'name']);
            } catch (\Exception $e) {
                // Might already exist
            }
        });
    }
};

