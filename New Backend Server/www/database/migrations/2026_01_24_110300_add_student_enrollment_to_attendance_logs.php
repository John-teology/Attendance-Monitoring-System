<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('user_id')->constrained('students')->nullOnDelete();
            $table->foreignId('enrollment_id')->nullable()->after('term_id')->constrained('enrollments')->nullOnDelete();
        });

        $logs = DB::table('attendance_logs')->select('attendance_logs.id', 'users.student_id')
            ->join('users', 'attendance_logs.user_id', '=', 'users.id')
            ->get();
        foreach ($logs as $l) {
            if ($l->student_id) {
                DB::table('attendance_logs')->where('id', $l->id)->update(['student_id' => $l->student_id]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enrollment_id');
            $table->dropConstrainedForeignId('student_id');
        });
    }
};
