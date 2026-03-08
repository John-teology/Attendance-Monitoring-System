<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropForeign(['year_level_id']);
            $table->unsignedBigInteger('year_level_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->unsignedBigInteger('year_level_id')->nullable(false)->change();
            $table->foreign('year_level_id')->references('id')->on('year_levels')->onDelete('cascade');
        });
    }
};
