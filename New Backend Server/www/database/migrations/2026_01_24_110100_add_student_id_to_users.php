<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->after('id')->constrained('students')->nullOnDelete();
        });

        $users = DB::table('users')->where('user_type', 'student')->get();
        foreach ($users as $u) {
            $studentId = DB::table('students')->where('student_number', $u->id_number)->value('id');
            if (!$studentId) {
                $studentId = DB::table('students')->insertGetId([
                    'student_number' => $u->id_number,
                    'full_name' => $u->full_name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('users')->where('id', $u->id)->update(['student_id' => $studentId]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_id');
        });
    }
};
