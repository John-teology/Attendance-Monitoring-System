<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->unsignedInteger('academic_year');
            $table->foreignId('year_level_id')->constrained('year_levels')->onDelete('cascade');
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->enum('term_type', ['semestral', 'quarteral']);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['student_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
