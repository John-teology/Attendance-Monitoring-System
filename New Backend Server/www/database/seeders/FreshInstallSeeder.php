<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FreshInstallSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('attendance_logs')->truncate();
        DB::table('users')->truncate();
        DB::table('students')->truncate();
        DB::table('enrollments')->truncate();
        
        // Reset system settings to defaults
        try {
            DB::table('system_settings')->truncate();
        } catch (\Throwable $e) {
            // Fallback if truncate fails on SQLite due to FK constraints
            DB::table('system_settings')->delete();
        }
        DB::table('system_settings')->insert([
            'school_name' => "Library Attendance",
            'school_logo' => null,
            'app_background_image' => null,
        ]);
        
        $this->call(LookupSeeder::class);
        $this->call(AdminSeeder::class);
        // $this->call(SampleDataSeeder::class);
    }
}
