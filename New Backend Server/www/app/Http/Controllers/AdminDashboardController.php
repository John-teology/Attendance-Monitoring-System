<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AttendanceLog;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'today');
        $termId = $request->input('term');

        // Set Date Range
        $startDate = Carbon::today()->startOfDay();
        $endDate = Carbon::today()->endOfDay();

        if ($filter === 'yesterday') {
            $startDate = Carbon::yesterday()->startOfDay();
            $endDate = Carbon::yesterday()->endOfDay();
        } elseif ($filter === 'last_7_days') {
            $startDate = Carbon::now()->subDays(6)->startOfDay(); // Include today
            $endDate = Carbon::now()->endOfDay();
        }

        $rangeStartMs = $startDate->timestamp * 1000;
        $rangeEndMs = $endDate->timestamp * 1000;

        // 1. Current Occupancy (Always Real-time, regardless of filter)
        // Occupancy is a "Right Now" metric.
        $todayStart = Carbon::today()->startOfDay()->timestamp * 1000;
        $todayLogs = AttendanceLog::where('scanned_at', '>=', $todayStart)
                                  ->when($termId, function($q) use ($termId) { $q->where('term_id', $termId); })
                                  ->orderBy('scanned_at', 'asc')
                                  ->get();
        
        $userStatus = [];
        foreach($todayLogs as $log) {
            $userStatus[$log->user_id] = $log->entry_type;
        }
        
        $currentOccupancy = 0;
        foreach($userStatus as $status) {
            if($status == 'IN') $currentOccupancy++;
        }

        // 2. Visits in Selected Range & Delta
        $rangeLogs = AttendanceLog::whereBetween('scanned_at', [$rangeStartMs, $rangeEndMs])
                                  ->when($termId, function($q) use ($termId) { $q->where('term_id', $termId); })
                                  ->orderBy('scanned_at', 'asc')
                                  ->get();

        if ($filter === 'last_7_days') {
            $totalVisitsInRange = 0;
            for ($i = 0; $i <= 6; $i++) {
                $dayStart = Carbon::now()->subDays($i)->startOfDay()->timestamp * 1000;
                $dayEnd = Carbon::now()->subDays($i)->endOfDay()->timestamp * 1000;
                $dailyCount = AttendanceLog::whereBetween('scanned_at', [$dayStart, $dayEnd])
                                             ->when($termId, function($q) use ($termId) { $q->where('term_id', $termId); })
                                             ->where('entry_type', 'IN')
                                             ->count(); // Count all 'IN' scans, not just unique users
                $totalVisitsInRange += $dailyCount;
            }
        } else {
            $totalVisitsInRange = AttendanceLog::whereBetween('scanned_at', [$rangeStartMs, $rangeEndMs])
                                               ->when($termId, function($q) use ($termId) { $q->where('term_id', $termId); })
                                               ->where('entry_type', 'IN')
                                               ->count(); // Count all 'IN' scans, not just unique users
        }

        // Calculate Delta (vs Previous Period)
        $previousStartMs = 0;
        $previousEndMs = 0;
        
        if ($filter === 'today') {
            $previousStartMs = Carbon::yesterday()->startOfDay()->timestamp * 1000;
            $previousEndMs = Carbon::yesterday()->endOfDay()->timestamp * 1000;
        } elseif ($filter === 'yesterday') {
            $previousStartMs = Carbon::today()->subDays(2)->startOfDay()->timestamp * 1000;
            $previousEndMs = Carbon::today()->subDays(2)->endOfDay()->timestamp * 1000;
        } elseif ($filter === 'last_7_days') {
            $previousStartMs = Carbon::now()->subDays(13)->startOfDay()->timestamp * 1000;
            $previousEndMs = Carbon::now()->subDays(7)->endOfDay()->timestamp * 1000;
        }

        if ($filter === 'last_7_days') {
            $totalVisitsPrevious = 0;
            for ($i = 7; $i <= 13; $i++) {
                $dayStart = Carbon::now()->subDays($i)->startOfDay()->timestamp * 1000;
                $dayEnd = Carbon::now()->subDays($i)->endOfDay()->timestamp * 1000;
                $prevCount = AttendanceLog::whereBetween('scanned_at', [$dayStart, $dayEnd])
                                           ->when($termId, function($q) use ($termId) { $q->where('term_id', $termId); })
                                           ->where('entry_type', 'IN')
                                           ->count();
                $totalVisitsPrevious += $prevCount;
            }
        } else {
            $totalVisitsPrevious = AttendanceLog::whereBetween('scanned_at', [$previousStartMs, $previousEndMs])
                                                 ->when($termId, function($q) use ($termId) { $q->where('term_id', $termId); })
                                                 ->where('entry_type', 'IN')
                                                 ->count();
        }

        $visitDelta = 0;
        if ($totalVisitsPrevious > 0) {
            $visitDelta = (($totalVisitsInRange - $totalVisitsPrevious) / $totalVisitsPrevious) * 100;
        } else {
            $visitDelta = $totalVisitsInRange > 0 ? 100 : 0;
        }

        // 3. Average Visit Duration (For Selected Range)
        $visitDurations = [];
        $userInTime = [];
        
        foreach($rangeLogs as $log) {
            if ($log->entry_type == 'IN') {
                $userInTime[$log->user_id] = $log->scanned_at;
            } elseif ($log->entry_type == 'OUT' && isset($userInTime[$log->user_id])) {
                $duration = $log->scanned_at - $userInTime[$log->user_id]; 
                $visitDurations[] = $duration;
                unset($userInTime[$log->user_id]); 
            }
        }
        
        $avgDurationText = "0m";
        if (count($visitDurations) > 0) {
            $avgMs = array_sum($visitDurations) / count($visitDurations);
            $avgMinutes = floor($avgMs / 60000);
            $hours = floor($avgMinutes / 60);
            $minutes = $avgMinutes % 60;
            
            if ($hours > 0) {
                $avgDurationText = "{$hours}h {$minutes}m";
            } else {
                $avgDurationText = "{$minutes}m";
            }
        }

        // 4. Traffic Analysis (Hourly or Daily based on filter)
        $hourlyTraffic = [];
        
        // Pre-fetch user types
        $userIds = $rangeLogs->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->pluck('user_type', 'id');

        if ($filter === 'last_7_days') {
            // Daily Traffic for 7 days
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i)->format('Y-m-d');
                $displayDate = Carbon::now()->subDays($i)->format('M d'); // e.g. Jan 15
                $hourlyTraffic[$displayDate] = ['student' => 0, 'faculty' => 0];
            }

            foreach($rangeLogs as $log) {
                if($log->entry_type == 'IN') {
                    $date = Carbon::createFromTimestampMs($log->scanned_at)
                                  ->setTimezone(config('app.timezone'))
                                  ->format('M d');
                    
                    if (isset($hourlyTraffic[$date])) {
                        $type = $users[$log->user_id] ?? 'student'; 
                        $type = strtolower($type) == 'faculty' ? 'faculty' : 'student';
                        $hourlyTraffic[$date][$type]++;
                    }
                }
            }
        } else {
            // Hourly Traffic (7 AM to 10 PM) for single day
            for ($h = 7; $h <= 22; $h++) {
                $hourlyTraffic[$h] = ['student' => 0, 'faculty' => 0];
            }

            foreach($rangeLogs as $log) {
                if($log->entry_type == 'IN') {
                    $hour = Carbon::createFromTimestampMs($log->scanned_at)
                                  ->setTimezone(config('app.timezone'))
                                  ->hour;
                    
                    if ($hour >= 7 && $hour <= 22) {
                        $type = $users[$log->user_id] ?? 'student'; 
                        $type = strtolower($type) == 'faculty' ? 'faculty' : 'student';
                        $hourlyTraffic[$hour][$type]++;
                    }
                }
            }
        }

        // 5-8: Demographics (Keep Global or Filtered? Let's keep Global for now as "Active Members" is a state)
        // ... (Existing Code for Active Users, Growth, Demographics, Top Courses) ...
        // We can optimize by reusing existing variables
        
        $activeUsers = User::where('status', 'active')->count();
        $memberGrowth = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $count = User::whereBetween('created_at', [$monthStart, $monthEnd])->count();
            $memberGrowth[] = $count;
        }
        $studentCount = User::where('user_type', 'student')->where('status', 'active')->count();
        $facultyCount = User::where('user_type', 'faculty')->where('status', 'active')->count();
        
        $topCourses = AttendanceLog::whereBetween('scanned_at', [$rangeStartMs, $rangeEndMs])
            ->when($termId, function($q) use ($termId) { $q->where('term_id', $termId); })
            ->where('entry_type', 'IN')
            ->join('users', 'attendance_logs.user_id', '=', 'users.id')
            ->join('courses', 'users.course_id', '=', 'courses.id')
            ->where('users.user_type', 'student')
            ->select('courses.name', DB::raw('count(distinct attendance_logs.user_id) as total'))
            ->groupBy('courses.name')
            ->orderByDesc('total')
            ->take(10)
            ->get()
            ->map(function($item) {
                return ['name' => $item->name, 'total' => $item->total];
            });

        $yearLevels = AttendanceLog::whereBetween('scanned_at', [$rangeStartMs, $rangeEndMs])
            ->when($termId, function($q) use ($termId) { $q->where('term_id', $termId); })
            ->where('entry_type', 'IN')
            ->join('users', 'attendance_logs.user_id', '=', 'users.id')
            ->join('year_levels', 'users.year_level_id', '=', 'year_levels.id')
            ->where('users.user_type', 'student')
            ->select('year_levels.name', DB::raw('count(distinct attendance_logs.user_id) as total'))
            ->groupBy('year_levels.name')
            ->get()
            ->sortBy('name') // Year levels usually sort nicely alphabetically (1st, 2nd, 3rd)
            ->map(function($item) {
                return ['name' => $item->name, 'total' => $item->total];
            })
            ->values();

        $terms = Term::orderBy('academic_year', 'desc')->orderBy('start_date')->get();

        // Return JSON if AJAX
        if ($request->ajax() && $request->header('X-CHART-REQUEST')) {
            return response()->json([
                'currentOccupancy' => $currentOccupancy,
                'totalVisits' => $totalVisitsInRange,
                'totalVisitsPrevious' => $totalVisitsPrevious,
                'visitDelta' => $visitDelta,
                'avgDurationText' => $avgDurationText,
                'hourlyTraffic' => $hourlyTraffic,
                'filter' => $filter
            ]);
        }

        return view('admin.dashboard', compact(
            'currentOccupancy', 
            'hourlyTraffic', 
            'totalVisitsInRange', // Renamed from totalVisitsToday
            'totalVisitsPrevious',
            'visitDelta',
            'activeUsers',
            'memberGrowth',
            'avgDurationText',
            'studentCount',
            'facultyCount',
            'topCourses',
            'yearLevels',
            'filter',
            'terms',
            'termId'
        ));
    }
}
