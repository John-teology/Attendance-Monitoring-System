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
            $table->string('school_name_color')->nullable()->after('app_background_image');
            $table->string('button_bg_color')->nullable()->after('school_name_color');
            $table->string('body_bg_color')->nullable()->after('button_bg_color');
            $table->string('font_style')->nullable()->default('Poppins')->after('body_bg_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn(['school_name_color', 'button_bg_color', 'body_bg_color', 'font_style']);
        });
    }
};
