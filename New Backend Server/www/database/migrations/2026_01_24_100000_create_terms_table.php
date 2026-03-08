<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['semestral', 'quarteral']);
            $table->foreignId('year_level_id')->constrained('year_levels')->onDelete('cascade');
            $table->unsignedInteger('academic_year'); // e.g. 2026
            $table->date('start_date');
            $table->date('end_date');
            $table->timestamps();
            $table->index(['year_level_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms');
    }
};
