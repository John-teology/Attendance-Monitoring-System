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
        Schema::table('system_settings', function (Blueprint $table) {
            $table->integer('card_transparency')->default(80)->after('font_style'); // 0-100 (Default 80% opacity)
            $table->integer('button_transparency')->default(100)->after('card_transparency'); // 0-100 (Default 100% solid)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['card_transparency', 'button_transparency']);
        });
    }
};
