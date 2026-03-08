<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\SystemSetting;
use App\Models\AttendanceLog;
use Carbon\Carbon;

Route::get('/kiosk', function () {
    $settings = SystemSetting::first();
    return view('kiosk', compact('settings'));
})->name('kiosk');

Route::get('/kiosk/stats', function () {
    $timezone = config('app.timezone');
    $startOfDay = Carbon::now($timezone)->startOfDay()->timestamp * 1000;

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
        'active_count' => $activeCount,
        'ongoing_count' => $ongoingCount
    ]);
})->name('kiosk.stats');
