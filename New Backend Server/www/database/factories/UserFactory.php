<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['student', 'faculty']);
        $idNumber = $this->faker->unique()->numerify('########');
        
        // Randomly pick IDs if tables exist and have data
        // We use try-catch or checks to avoid errors if tables are empty
        $deptId = \Illuminate\Support\Facades\DB::table('departments')->inRandomOrder()->value('id');
        
        $courseId = null;
        $yearId = null;
        $desigId = null;

        if ($type == 'student') {
            $courseId = \Illuminate\Support\Facades\DB::table('courses')->inRandomOrder()->value('id');
            $yearId = \Illuminate\Support\Facades\DB::table('year_levels')->inRandomOrder()->value('id');
        } else {
            $desigId = \Illuminate\Support\Facades\DB::table('designations')->inRandomOrder()->value('id');
        }
        
        return [
            'full_name' => $this->faker->name(),
            'id_number' => $idNumber,
            'user_type' => $type,
            'qr_code' => $idNumber, // Match ID for simplicity
            'rfid_uid' => $this->faker->unique()->numerify('##########'),
            'status' => 'active',
            'department_id' => $deptId,
            'course_id' => $courseId,
            'year_level_id' => $yearId,
            'designation_id' => $desigId,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
