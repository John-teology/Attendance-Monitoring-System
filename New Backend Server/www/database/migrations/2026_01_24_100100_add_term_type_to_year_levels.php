<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('year_levels', function (Blueprint $table) {
            $table->enum('term_type', ['semestral', 'quarteral'])->default('semestral')->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('year_levels', function (Blueprint $table) {
            $table->dropColumn('term_type');
        });
    }
};
