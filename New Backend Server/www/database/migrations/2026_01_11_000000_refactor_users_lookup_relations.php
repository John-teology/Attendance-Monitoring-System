<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add new ID columns
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null')->after('department');
            $table->foreignId('course_id')->nullable()->constrained('courses')->onDelete('set null')->after('course');
            $table->foreignId('designation_id')->nullable()->constrained('designations')->onDelete('set null')->after('designation');
            $table->foreignId('year_level_id')->nullable()->constrained('year_levels')->onDelete('set null')->after('year_level');
        });

        // 2. Migrate Data
        // We need to fetch all users and update their IDs based on the text values
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            $updates = [];

            if ($user->department) {
                $dept = DB::table('departments')->where('name', $user->department)->first();
                if ($dept) $updates['department_id'] = $dept->id;
                // Optional: If not found, maybe create it? For now, leave null if not found.
            }

            if ($user->course) {
                $course = DB::table('courses')->where('name', $user->course)->first();
                if ($course) $updates['course_id'] = $course->id;
            }

            if ($user->designation) {
                $desig = DB::table('designations')->where('name', $user->designation)->first();
                if ($desig) $updates['designation_id'] = $desig->id;
            }

            if ($user->year_level) {
                $year = DB::table('year_levels')->where('name', $user->year_level)->first();
                if ($year) $updates['year_level_id'] = $year->id;
            }

            if (!empty($updates)) {
                DB::table('users')->where('id', $user->id)->update($updates);
            }
        }

        // 3. Drop old text columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['department', 'course', 'designation', 'year_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable();
            $table->string('course')->nullable();
            $table->string('designation')->nullable();
            $table->string('year_level')->nullable();
        });

        // Restore text data from IDs
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $updates = [];
            if ($user->department_id) {
                $dept = DB::table('departments')->find($user->department_id);
                if ($dept) $updates['department'] = $dept->name;
            }
            if ($user->course_id) {
                $course = DB::table('courses')->find($user->course_id);
                if ($course) $updates['course'] = $course->name;
            }
            if ($user->designation_id) {
                $desig = DB::table('designations')->find($user->designation_id);
                if ($desig) $updates['designation'] = $desig->name;
            }
            if ($user->year_level_id) {
                $year = DB::table('year_levels')->find($user->year_level_id);
                if ($year) $updates['year_level'] = $year->name;
            }
            if (!empty($updates)) {
                DB::table('users')->where('id', $user->id)->update($updates);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['course_id']);
            $table->dropForeign(['designation_id']);
            $table->dropForeign(['year_level_id']);
            $table->dropColumn(['department_id', 'course_id', 'designation_id', 'year_level_id']);
        });
    }
};
