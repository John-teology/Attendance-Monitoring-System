<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\AttendanceLog;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Auto-Timeout Users at Midnight
 * Finds users who are still 'IN' from the previous day and logs them 'OUT'.
 */
Artisan::command('attendance:auto-timeout', function () {
    $this->info('Running Auto-Timeout...');
    
    // Find all unique users who have logged today
    // Logic:
    // 1. Get the last log for every user.
    // 2. If the last log is 'IN' and it was before midnight today (i.e., yesterday or older), mark as OUT.
    // Actually, simpler: Find any user whose *very last* log entry in the entire DB is 'IN'.
    
    // Subquery to get the latest log ID for each user
    $latestLogIds = AttendanceLog::selectRaw('MAX(id) as id')
        ->groupBy('user_id')
        ->pluck('id');
        
    $stuckUsers = AttendanceLog::whereIn('id', $latestLogIds)
        ->where('entry_type', 'IN')
        ->get();
        
    $count = 0;
    foreach ($stuckUsers as $log) {
        // If the scan was from today (since midnight), don't timeout yet.
        // We only timeout if the scan was *before* today (yesterday).
        $logDate = Carbon::createFromTimestampMs($log->scanned_at)->startOfDay();
        $today = Carbon::today();
        
        if ($logDate->lt($today)) {
            // It's an old 'IN'. Time them out.
            // Set timeout time to 11:59 PM of that day, or just now?
            // Usually 11:59 PM of the day they entered looks cleaner for records.
            
            $timeoutTimestamp = Carbon::createFromTimestampMs($log->scanned_at)->endOfDay()->timestamp * 1000;
            
            AttendanceLog::create([
                'user_id' => $log->user_id,
                'scan_type' => 'AUTO',
                'entry_type' => 'OUT',
                'scanned_at' => $timeoutTimestamp
            ]);
            
            $this->info("Auto-timed out User ID: {$log->user_id}");
            $count++;
        }
    }
    
    $this->info("Auto-Timeout Complete. Processed $count users.");
    
})->purpose('Automatically logs out users who forgot to time out');

// Schedule the command to run daily at 00:00 (Midnight)
Schedule::command('attendance:auto-timeout')->daily();
