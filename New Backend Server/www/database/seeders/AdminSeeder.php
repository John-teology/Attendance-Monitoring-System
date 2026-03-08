<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure only two admin accounts exist
        Admin::query()->delete();
        
        Admin::create([
            'name' => 'DTR INNOVATION SOLUTIONS',
            'email' => 'superadmin@dtrsol.com',
            'password' => '$2y$12$mkXcsl.l8cenn8Q6Bmr.jumj5ZLXTmpK9VDc1gTwYMoxhPkvPgyr2',
            'role' => 'super_admin',
        ]);
        
        Admin::create([
            'name' => 'Admin User',
            'email' => 'admin@school.edu',
            'password' => '$2y$12$mkXcsl.l8cenn8Q6Bmr.jumj5ZLXTmpK9VDc1gTwYMoxhPkvPgyr2',
            'role' => 'admin',
        ]);
    }
}
