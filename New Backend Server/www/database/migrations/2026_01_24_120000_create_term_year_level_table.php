<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_year_level', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained('terms')->onDelete('cascade');
            $table->foreignId('year_level_id')->constrained('year_levels')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['term_id', 'year_level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_year_level');
    }
};
