<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use App\Models\Term;
use App\Models\Student;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\SystemSetting;

class AttendanceController extends Controller
{
    public function getUsers()
    {
        // Fetch all users so the app can validate status locally
        $users = User::with(['department', 'course', 'yearLevel', 'designation'])
            ->select('id', 'full_name', 'id_number', 'user_type', 'qr_code', 'rfid_uid', 'status', 'id_expiration_date', 'department_id', 'course_id', 'year_level_id', 'designation_id', 'profile_picture')
            ->get();

        // Map to flatten structure for App compatibility
        $users = $users->map(function($user) {
            $u = $user->toArray();
            $u['department'] = $user->department->name ?? '';
            $u['course'] = $user->course->name ?? '';
            $u['year_level'] = $user->yearLevel->name ?? '';
            $u['designation'] = $user->designation->name ?? '';
            // Send relative path so Android can construct URL dynamically based on its settings
            $u['profile_picture_url'] = $user->profile_picture; 
            return $u;
        });

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    public function getStats()
    {
        $timezone = config('app.timezone');
        $startOfDay = \Carbon\Carbon::now($timezone)->startOfDay()->timestamp * 1000;

        // 1. Active Count: Unique users scanned today
        $activeCount = AttendanceLog::where('scanned_at', '>=', $startOfDay)
            ->distinct('user_id')
            ->count('user_id');

        // 2. Ongoing Count: Users currently IN
        // Get all logs for today, grouped by user to find their latest status
        $todayLogs = AttendanceLog::where('scanned_at', '>=', $startOfDay)
            ->orderBy('scanned_at', 'asc')
            ->get();

        $userStatus = [];
        foreach ($todayLogs as $log) {
            $userStatus[$log->user_id] = $log->entry_type; // Overwrite with latest status
        }

        $ongoingCount = 0;
        foreach ($userStatus as $status) {
            if ($status === 'IN') {
                $ongoingCount++;
            }
        }

        return response()->json([
            'success' => true,
            'active_count' => $activeCount,
            'ongoing_count' => $ongoingCount
        ]);
    }

    /**
     * Real-time verification of user by QR Code
     */
    public function verify(Request $request)
    {
        $code = trim((string) $request->input('code', ''));
        
        if ($code === '') {
            return response()->json(['success' => false, 'message' => 'No code provided']);
        }

        $scanType = strtolower(trim((string) $request->input('entry_type', $request->input('scan_type', ''))));

        $query = User::with(['department', 'course', 'yearLevel', 'designation']);
        if ($scanType === 'rfid') {
            $query->where('rfid_uid', $code);
        } elseif ($scanType === 'qr') {
            $query->where('id_number', $code);
        } else {
            $query->where(function($q) use ($code) {
                $q->where('qr_code', $code)
                    ->orWhere('id_number', $code)
                    ->orWhere('rfid_uid', $code)
                    ->orWhere('id', $code);
            });
        }

        $user = $query->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Access Denied: User not found']);
        }

        // Prepare User Data for App (Flatten relations)
        $userData = $user->toArray();
        $userData['department'] = $user->department->name ?? '';
        $userData['course'] = $user->course->name ?? '';
        $userData['year_level'] = $user->yearLevel->name ?? '';
        $userData['designation'] = $user->designation->name ?? '';
        // Send relative path so Android can construct URL dynamically
        $userData['profile_picture_url'] = $user->profile_picture; 

        // Check Expiration
        if ($user->id_expiration_date && \Carbon\Carbon::parse($user->id_expiration_date)->endOfDay()->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: ID Expired on ' . $user->id_expiration_date,
                'user' => $userData
            ]);
        }

        // Check Status
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied: User is Inactive',
                'user' => $userData
            ]);
        }

        // Determine Entry Status (IN/OUT prediction)
        $lastLog = AttendanceLog::where('user_id', $user->id)
                                ->orderBy('scanned_at', 'desc')
                                ->first();
        
        $nextAction = 'IN';
        if ($lastLog && $lastLog->entry_type == 'IN') {
            $nextAction = 'OUT';
        }

        return response()->json([
            'success' => true,
            'message' => 'User Verified',
            'user' => $userData,
            'next_action' => $nextAction
        ]);
    }

    public function sync(Request $request)
    {
        try {
            $logs = $request->all();
            
            // If request body is a single object, wrap it in array
            // Check for keys that indicate a single log entry
            if (array_key_exists('user_id', $logs) || array_key_exists('code', $logs) || isset($logs['id_number']) || isset($logs['rfid_uid'])) {
                $logs = [$logs];
            }

            $syncedIds = [];

            foreach ($logs as $logData) {
                // Validate required fields
                if (!isset($logData['timestamp'])) {
                    continue;
                }

                $user = null;
                // Look up user by User ID (Primary Key), then ID Number, then RFID, then QR Code
                if (isset($logData['user_id'])) {
                    $user = User::find($logData['user_id']);
                } elseif (isset($logData['id_number'])) {
                    $user = User::where('id_number', trim($logData['id_number']))->first();
                } elseif (isset($logData['rfid_uid'])) {
                    Log::info('Searching for RFID: ' . $logData['rfid_uid']);
                    $user = User::where('rfid_uid', trim($logData['rfid_uid']))->first();
                } elseif (isset($logData['qr_code'])) {
                    $code = trim($logData['qr_code']);
                    if ($code !== '') {
                        // User specified strict QR check on id_number
                        $user = User::where('id_number', $code)->first();
                    }
                } elseif (isset($logData['code'])) { // Some apps send 'code'
                    $code = trim($logData['code']);
                    if ($code !== '') {
                        // Determine scan type from log data
                        $scanType = isset($logData['entry_type']) ? strtolower($logData['entry_type']) : (isset($logData['scan_type']) ? strtolower($logData['scan_type']) : '');

                        if ($scanType === 'rfid') {
                             $user = User::where('rfid_uid', $code)->first();
                        } elseif ($scanType === 'qr') {
                             $user = User::where('id_number', $code)->first();
                        } else {
                            // Universal search: Check ID Number, QR, RFID, and Primary Key
                            $user = User::where(function($query) use ($code) {
                                $query->where('id_number', $code)
                                      ->orWhere('qr_code', $code)
                                      ->orWhere('rfid_uid', $code)
                                      ->orWhere('id', $code);
                            })->first();
                        }
                    }
                }

                if (!$user) {
                    Log::warning("Sync failed for log: User not found", $logData);
                    // If single log, return error so app shows Access Denied
                    if (count($logs) == 1) {
                         return response()->json([
                            'success' => false,
                            'message' => "Access Denied: User not found"
                        ]);
                    }
                    continue;
                }

                // --- ACCESS CONTROL CHECK ---
                // 1. Check ID Expiration
                if ($user->id_expiration_date && \Carbon\Carbon::parse($user->id_expiration_date)->endOfDay()->isPast()) {
                    // Auto-update status to inactive if expired
                    if ($user->status !== 'inactive') {
                        $user->update(['status' => 'inactive']);
                    }
                    
                    if (count($logs) == 1) {
                        return response()->json([
                            'success' => false,
                            'message' => "Access Denied: ID Expired on " . $user->id_expiration_date
                        ]);
                    }
                    continue; // Skip processing this log
                }

                // 2. Check Inactive Status
                if ($user->status !== 'active') {
                    if (count($logs) == 1) {
                        return response()->json([
                            'success' => false,
                            'message' => "Access Denied: Account is Inactive"
                        ]);
                    }
                    continue; // Skip processing this log
                }
                // -----------------------------

                $serverTimestamp = now()->timestamp * 1000;

                // --- RESTRICTION LOGIC ---
                // Fetch the latest log for this user
                $lastLog = AttendanceLog::where('user_id', $user->id)
                                        ->orderBy('scanned_at', 'desc')
                                        ->first();

                $entryType = 'IN'; // Default

                if ($lastLog) {
                    $timeDiff = ($serverTimestamp - $lastLog->scanned_at) / 1000; // Difference in seconds

                    // 1. If scan is less than 1 minute (60 seconds) from last scan, SKIP
                    if ($timeDiff < 60) {
                        Log::info("Skipped scan for User {$user->id}: Too fast ($timeDiff seconds)");
                        
                        // Don't mark as synced if it was a real-time scan request so the app knows it failed logic
                        // But if it's a background sync of an old log, we might want to just ack it.
                        // For the purpose of the "Too fast" prompt in the app, we should return a specific error or status if possible.
                        // However, since this endpoint handles batch sync, complex error reporting per item is tricky.
                        // But for the single-item "real time" check the app does via sendRawScan, we can return a warning.
                        
                        if (count($logs) == 1) {
                             $typeMsg = ($lastLog->entry_type == 'IN') ? "Time in" : "Time out";
                             return response()->json([
                                'success' => false,
                                'message' => "Too fast! $typeMsg was already recorded moments ago."
                            ]);
                        }

                        // For batch sync, we just ack it to clear the queue
                        if (isset($logData['id'])) {
                            $syncedIds[] = $logData['id'];
                        }
                        continue; 
                    }

                    // 2. If scan is after 1 minute (60 seconds), check for OUT
                    // If last was IN, and it's been > 1 minute, mark as OUT
                    if ($lastLog->entry_type == 'IN') {
                        // If previous entry was IN
                        if ($timeDiff > 60) {
                            // And it's been more than 1 minute, this is an OUT
                            $entryType = 'OUT';
                        }
                    } else {
                        // Last was OUT. New entry is IN.
                        $entryType = 'IN';
                    }
                }
                // -------------------------

                // Create or update log
                $termId = null;
                $studentId = null;
                $enrollmentId = null;
                try {
                    $scanDate = \Carbon\Carbon::createFromTimestampMs($serverTimestamp)->toDateString();
                    $ylId = $user->year_level_id;
                    if ($ylId) {
                        $term = Term::whereDate('start_date', '<=', $scanDate)
                                    ->whereDate('end_date', '>=', $scanDate)
                                    ->whereHas('yearLevels', function($q) use ($ylId) {
                                        $q->where('year_levels.id', $ylId);
                                    })
                                    ->orderBy('start_date', 'desc')
                                    ->first();
                        if ($term) {
                            $termId = $term->id;
                        }
                    }

                    if ($user->user_type === 'student') {
                        if ($user->student_id) {
                            $studentId = $user->student_id;
                        } else {
                            $student = Student::firstOrCreate(
                                ['student_number' => $user->id_number],
                                ['full_name' => $user->full_name]
                            );
                            $user->student_id = $student->id;
                            $user->save();
                            $studentId = $student->id;
                        }

                        if ($studentId) {
                            $enroll = Enrollment::where('student_id', $studentId)
                                ->whereDate('start_date', '<=', $scanDate)
                                ->whereDate('end_date', '>=', $scanDate)
                                ->orderBy('start_date', 'desc')
                                ->first();
                            if (!$enroll) {
                                $ay = \Carbon\Carbon::createFromTimestampMs($serverTimestamp)->year;
                                $tt = null;
                                if ($user->year_level_id) {
                                    $tt = \App\Models\YearLevel::where('id', $user->year_level_id)->value('term_type') ?: 'semestral';
                                } else {
                                    $tt = 'semestral';
                                }
                                $start = \Carbon\Carbon::create($ay, 1, 1)->toDateString();
                                $end = \Carbon\Carbon::create($ay, 12, 31)->toDateString();
                                $enroll = Enrollment::create([
                                    'student_id' => $studentId,
                                    'academic_year' => $ay,
                                    'year_level_id' => $user->year_level_id,
                                    'course_id' => $user->course_id,
                                    'term_type' => $tt,
                                    'start_date' => $start,
                                    'end_date' => $end,
                                    'status' => 'active',
                                ]);
                            }
                            $enrollmentId = $enroll ? $enroll->id : null;
                        }
                    }
                } catch (\Throwable $t) {
                    Log::warning('Term resolution failed: ' . $t->getMessage());
                }

                AttendanceLog::create([
                    'user_id' => $user->id,
                    'term_id' => $termId,
                    'student_id' => $studentId,
                    'enrollment_id' => $enrollmentId,
                    'scan_type' => $logData['scan_type'] ?? (isset($logData['rfid_uid']) ? 'RFID' : 'QR'),
                    'entry_type' => $entryType,
                    'scanned_at' => $serverTimestamp, 
                ]);

                // Collect ID to return to Android app so it can mark as synced
                if (isset($logData['id'])) {
                    $syncedIds[] = $logData['id'];
                }

                // If real-time scan (single log), return specific success message with entry type and User Data
                if (count($logs) == 1) {
                    $user->load(['department', 'course', 'yearLevel', 'designation']);
                    $userData = $user->toArray();
                    $userData['department'] = $user->department->name ?? '';
                    $userData['course'] = $user->course->name ?? '';
                    $userData['year_level'] = $user->yearLevel->name ?? '';
                    $userData['designation'] = $user->designation->name ?? '';
                    $userData['profile_picture_url'] = $user->profile_picture; // Send relative path

                    return response()->json([
                        'success' => true,
                        'message' => 'Logs synced successfully',
                        'syncedIds' => $syncedIds,
                        'entry_type' => $entryType, // Return IN or OUT
                        'user' => $userData // Return User object for app display
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Logs synced successfully',
                'syncedIds' => $syncedIds
            ]);

        } catch (\Exception $e) {
            Log::error('Sync failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSettings()
    {
        $settings = SystemSetting::first();
        
        $bgUrl = null;
        if ($settings && $settings->app_background_image) {
            $bgUrl = asset('storage/' . $settings->app_background_image);
        }

        $logoUrl = null;
        if ($settings && $settings->school_logo) {
            $logoUrl = asset('storage/' . $settings->school_logo);
        }

        return response()->json([
            'school_name' => $settings->school_name ?? "Saint Mary's Academy",
            'school_logo_url' => $logoUrl,
            'app_background_image' => $bgUrl,
            'school_name_color' => $settings->school_name_color ?? '#000000',
            'button_bg_color' => $settings->button_bg_color ?? '#0d6efd',
            'body_bg_color' => $settings->body_bg_color ?? '#f8f9fa',
            'font_style' => $settings->font_style ?? 'Poppins',
            'card_transparency' => $settings->card_transparency ?? 80,
            'button_transparency' => $settings->button_transparency ?? 100,
            'icon_color' => $settings->icon_color ?? '#10B981',
            'volume_level' => $settings->volume_level, // Can be null
        ]);
    }
}
