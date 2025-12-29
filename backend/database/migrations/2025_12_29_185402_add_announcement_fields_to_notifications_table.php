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
        Schema::table('notifications', function (Blueprint $table) {
            $table->boolean('is_announcement')->default(false)->after('is_read');
            $table->string('priority', 20)->default('normal')->after('is_announcement'); // 'low', 'normal', 'high', 'urgent'
            $table->boolean('email_sent')->default(false)->after('priority');
            $table->timestamp('email_sent_at')->nullable()->after('email_sent');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->after('user_id');
            
            $table->index(['is_announcement', 'is_read']);
            $table->index(['priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['is_announcement', 'is_read']);
            $table->dropIndex(['priority']);
            $table->dropColumn(['is_announcement', 'priority', 'email_sent', 'email_sent_at', 'created_by']);
        });
    }
};
