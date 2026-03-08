<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $departments = ['College of Arts', 'College of Business', 'College of Education'];
        $courses = ['BS Accountancy', 'BS Education', 'BS Information Technology'];
        $designations = ['Librarian', 'Staff', 'Teacher'];
        $years = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

        foreach ($departments as $name) {
            DB::table('departments')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
        foreach ($courses as $name) {
            DB::table('courses')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
        foreach ($designations as $name) {
            DB::table('designations')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
        foreach ($years as $name) {
            DB::table('year_levels')->updateOrInsert(['name' => $name], ['name' => $name]);
        }
    }
}
