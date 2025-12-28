<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table exists
        if (!Schema::hasTable('subscription_prices')) {
            return;
        }

        // Check what columns actually exist using raw query
        $columns = DB::select("SHOW COLUMNS FROM subscription_prices");
        $columnNames = array_map(function($col) {
            return $col->Field;
        }, $columns);
        
        // Only add school_id if it doesn't exist
        if (!in_array('school_id', $columnNames)) {
            Schema::table('subscription_prices', function (Blueprint $table) {
                $table->foreignId('school_id')->nullable()->after('id')->constrained('schools')->onDelete('cascade');
            });
        }
        
        // Check if unique constraint exists
        $indexes = DB::select("SHOW INDEXES FROM subscription_prices WHERE Key_name = 'subscription_prices_unique_constraint'");
        if (empty($indexes)) {
            // Check if there's an old unique constraint we need to drop first
            $oldIndexes = DB::select("SHOW INDEXES FROM subscription_prices WHERE Key_name LIKE '%unique%'");
            foreach ($oldIndexes as $oldIndex) {
                try {
                    DB::statement("ALTER TABLE subscription_prices DROP INDEX {$oldIndex->Key_name}");
                } catch (\Exception $e) {
                    // Ignore errors
                }
            }
            
            // Add new unique constraint
            try {
                Schema::table('subscription_prices', function (Blueprint $table) {
                    $table->unique(['school_id', 'name'], 'subscription_prices_unique_constraint');
                });
            } catch (\Exception $e) {
                // Might already exist, ignore
            }
        }
        
        // Check if school_id index exists
        $schoolIdIndexes = DB::select("SHOW INDEXES FROM subscription_prices WHERE Column_name = 'school_id' AND Key_name = 'subscription_prices_school_id_index'");
        if (empty($schoolIdIndexes) && in_array('school_id', $columnNames)) {
            try {
                Schema::table('subscription_prices', function (Blueprint $table) {
                    $table->index('school_id', 'subscription_prices_school_id_index');
                });
            } catch (\Exception $e) {
                // Index might already exist
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new constraint and index
        try {
            Schema::table('subscription_prices', function (Blueprint $table) {
                $table->dropUnique('subscription_prices_unique_constraint');
            });
        } catch (\Exception $e) {
            // Might not exist
        }
        
        try {
            Schema::table('subscription_prices', function (Blueprint $table) {
                $table->dropIndex('subscription_prices_school_id_index');
            });
        } catch (\Exception $e) {
            // Might not exist
        }
        
        // Drop school_id
        $columns = DB::select("SHOW COLUMNS FROM subscription_prices");
        $columnNames = array_map(function($col) {
            return $col->Field;
        }, $columns);
        
        if (in_array('school_id', $columnNames)) {
            try {
                Schema::table('subscription_prices', function (Blueprint $table) {
                    $table->dropForeign(['school_id']);
                });
            } catch (\Exception $e) {
                // Might not exist
            }
            
            Schema::table('subscription_prices', function (Blueprint $table) {
                $table->dropColumn('school_id');
            });
        }
    }
};
