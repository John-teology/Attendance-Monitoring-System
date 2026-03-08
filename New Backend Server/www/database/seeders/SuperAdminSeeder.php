<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $name = env('SUPER_ADMIN_NAME', 'Super Admin');
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@school.edu');
        $password = 'password@)@^';

        if (!Admin::where('role', 'super_admin')->exists()) {
            Admin::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'super_admin',
            ]);
            $this->command->info("Super Admin created. Email: {$email}");
        } else {
            $this->command->info('Super Admin already exists.');
        }
    }
}
