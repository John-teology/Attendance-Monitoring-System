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
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->index('scanned_at');
            $table->index(['user_id', 'scanned_at']);
            $table->index(['entry_type', 'scanned_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('status');
            $table->index('user_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropIndex(['scanned_at']);
            $table->dropIndex(['attendance_logs_user_id_scanned_at_index']);
            $table->dropIndex(['attendance_logs_entry_type_scanned_at_index']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['user_type']);
        });
    }
};
