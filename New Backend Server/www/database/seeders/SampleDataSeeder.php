<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Department;
use App\Models\Course;
use App\Models\YearLevel;
use App\Models\Term;
use App\Models\User;
use App\Models\Student;
use App\Models\Enrollment;
use App\Models\AttendanceLog;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure base lookups exist
        $dept = Department::firstOrCreate(['name' => 'College of Information']);
        $courseIT = Course::firstOrCreate(['name' => 'BS Information Technology']);

        // Year Levels and term types
        $grade1 = YearLevel::firstOrCreate(['name' => 'Grade 1'], ['term_type' => 'quarteral']);
        $grade2 = YearLevel::firstOrCreate(['name' => 'Grade 2'], ['term_type' => 'quarteral']);
        $grade3 = YearLevel::firstOrCreate(['name' => 'Grade 3'], ['term_type' => 'quarteral']);
        $grade4 = YearLevel::firstOrCreate(['name' => 'Grade 4'], ['term_type' => 'quarteral']);
        $firstYear = YearLevel::firstOrCreate(['name' => '1st Year'], ['term_type' => 'semestral']);
        $secondYear = YearLevel::firstOrCreate(['name' => '2nd Year'], ['term_type' => 'semestral']);
        $thirdYear = YearLevel::firstOrCreate(['name' => '3rd Year'], ['term_type' => 'semestral']);
        $fourthYear = YearLevel::firstOrCreate(['name' => '4th Year'], ['term_type' => 'semestral']);

        // Academic Years range
        $years = [2024, 2025, 2026];

        // Create Terms for each academic year and level
        foreach ($years as $ay) {
            $yearStart = Carbon::create($ay, 6, 1); // Start of school year
            $yearEnd = Carbon::create($ay + 1, 3, 31); // End of school year

            // Quarteral: 4 quarters for grade school levels
            $quarters = [
                ['Q1', $yearStart->copy()->addWeeks(0), $yearStart->copy()->addWeeks(9)],
                ['Q2', $yearStart->copy()->addWeeks(10), $yearStart->copy()->addWeeks(19)],
                ['Q3', $yearStart->copy()->addWeeks(20), $yearStart->copy()->addWeeks(29)],
                ['Q4', $yearStart->copy()->addWeeks(30), $yearStart->copy()->addWeeks(39)],
            ];
            foreach ($quarters as [$name, $start, $end]) {
                $term = Term::firstOrCreate([
                    'name' => $name,
                    'type' => 'quarteral',
                    'academic_year' => $ay,
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ], ['year_level_id' => null]);
                $term->yearLevels()->syncWithoutDetaching([$grade1->id, $grade2->id, $grade3->id, $grade4->id]);
            }

            // Semestral: 2 semesters for college levels
            $sem1Start = $yearStart->copy();
            $sem1End = $yearStart->copy()->addMonths(4);
            $sem2Start = $sem1End->copy()->addWeek();
            $sem2End = $yearEnd->copy();
            $sem1 = Term::firstOrCreate([
                'name' => 'Semester 1',
                'type' => 'semestral',
                'academic_year' => $ay,
                'start_date' => $sem1Start->toDateString(),
                'end_date' => $sem1End->toDateString(),
            ], ['year_level_id' => null]);
            $sem1->yearLevels()->syncWithoutDetaching([$firstYear->id, $secondYear->id, $thirdYear->id, $fourthYear->id]);
            $sem2 = Term::firstOrCreate([
                'name' => 'Semester 2',
                'type' => 'semestral',
                'academic_year' => $ay,
                'start_date' => $sem2Start->toDateString(),
                'end_date' => $sem2End->toDateString(),
            ], ['year_level_id' => null]);
            $sem2->yearLevels()->syncWithoutDetaching([$firstYear->id, $secondYear->id, $thirdYear->id, $fourthYear->id]);
        }

        // Students: one per year level (Grade 1–4 and College 1st–4th), with logs across 2024–2026
        $students = [
            ['S-G1-001', 'Gina Grade1', $grade1, null],
            ['S-G2-001', 'Gary Grade2', $grade2, null],
            ['S-G3-001', 'Grace Grade3', $grade3, null],
            ['S-G4-001', 'Glen Grade4', $grade4, null],
            ['S-C1-001', 'Carl College1', $firstYear, $courseIT],
            ['S-C2-001', 'Cora College2', $secondYear, $courseIT],
            ['S-C3-001', 'Chris College3', $thirdYear, $courseIT],
            ['S-C4-001', 'Cathy College4', $fourthYear, $courseIT],
        ];

        foreach ($students as [$studNo, $name, $yl, $course]) {
            $student = Student::firstOrCreate(['student_number' => $studNo], ['full_name' => $name]);
            $user = User::updateOrCreate(
                ['id_number' => $studNo],
                [
                    'user_type' => 'student',
                    'full_name' => $name,
                    'qr_code' => Str::random(10),
                    'rfid_uid' => null,
                    'status' => 'active',
                    'department_id' => $dept->id,
                    'course_id' => $course ? $course->id : null,
                    'year_level_id' => $yl->id,
                    'designation_id' => null,
                    'profile_picture' => null,
                    'id_expiration_date' => null,
                    'student_id' => $student->id,
                ]
            );

            foreach ($years as $ay) {
                $yearStart = Carbon::create($ay, 6, 1);
                $yearEnd = Carbon::create($ay + 1, 3, 31);
                $termType = $yl->term_type ?? (str_contains($yl->name, 'Year') ? 'semestral' : 'quarteral');

                $enroll = Enrollment::firstOrCreate([
                    'student_id' => $student->id,
                    'academic_year' => $ay,
                    'year_level_id' => $yl->id,
                    'course_id' => $course ? $course->id : null,
                    'term_type' => $termType,
                    'start_date' => $yearStart->toDateString(),
                    'end_date' => $yearEnd->toDateString(),
                    'status' => 'active',
                ]);

                $terms = Term::where('academic_year', $ay)
                    ->whereHas('yearLevels', function($q) use ($yl) {
                        $q->where('year_levels.id', $yl->id);
                    })->get();
                foreach ($terms as $t) {
                    for ($i = 0; $i < 3; $i++) {
                        $start = Carbon::parse($t->start_date);
                        $end = Carbon::parse($t->end_date);
                        $span = max(2, $end->diffInDays($start) - 2);
                        $visitDate = $start->copy()->addDays(rand(1, $span));
                        $timestampMs = $visitDate->startOfDay()->timestamp * 1000 + rand(8 * 3600, 17 * 3600) * 1000;
                        AttendanceLog::create([
                            'user_id' => $user->id,
                            'student_id' => $student->id,
                            'enrollment_id' => $enroll->id,
                            'term_id' => $t->id,
                            'scan_type' => 'QR',
                            'entry_type' => ($i % 2 === 0) ? 'IN' : 'OUT',
                            'scanned_at' => $timestampMs,
                        ]);
                    }
                }
            }
        }
    }
}
