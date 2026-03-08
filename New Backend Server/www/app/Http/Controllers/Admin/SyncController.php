<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\AttendanceLog;
use PDO;

class SyncController extends Controller
{
    public function index()
    {
        return view('admin.sync.index');
    }

    public function downloadSqlite()
    {
        // Path for the temporary SQLite database
        $tempDbName = 'mobile_sync_' . now()->timestamp . '.sqlite';
        $tempDbPath = storage_path('app/temp/' . $tempDbName);
        
        // Ensure directory exists
        if (!file_exists(dirname($tempDbPath))) {
            mkdir(dirname($tempDbPath), 0755, true);
        }

        // Create new SQLite file
        touch($tempDbPath);

        // Connect to the temporary SQLite database
        $pdo = new PDO('sqlite:' . $tempDbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1. Create Users Table (Matching Android Schema)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_type TEXT,
                full_name TEXT,
                id_number TEXT UNIQUE,
                qr_code TEXT,
                rfid_uid TEXT,
                status TEXT,
                department_id INTEGER,
                course_id INTEGER,
                year_level_id INTEGER,
                designation_id INTEGER,
                id_expiration_date TEXT,
                created_at TEXT,
                updated_at TEXT
            )
        ");

        // 2. Create Attendance Logs Table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS attendance_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                scan_type TEXT,
                entry_type TEXT,
                scanned_at INTEGER,
                created_at TEXT,
                updated_at TEXT,
                sync_status INTEGER DEFAULT 1
            )
        ");

        // 3. Migrate Data from MySQL/Main DB to SQLite
        
        // Users
        $users = User::all();
        $stmtUser = $pdo->prepare("
            INSERT INTO users (
                id, user_type, full_name, id_number, qr_code, rfid_uid, 
                status, department_id, course_id, year_level_id, designation_id, 
                id_expiration_date, created_at, updated_at
            ) VALUES (
                :id, :user_type, :full_name, :id_number, :qr_code, :rfid_uid, 
                :status, :department_id, :course_id, :year_level_id, :designation_id, 
                :id_expiration_date, :created_at, :updated_at
            )
        ");

        foreach ($users as $user) {
            $stmtUser->execute([
                ':id' => $user->id,
                ':user_type' => $user->user_type,
                ':full_name' => $user->full_name,
                ':id_number' => $user->id_number,
                ':qr_code' => $user->qr_code,
                ':rfid_uid' => $user->rfid_uid,
                ':status' => $user->status,
                ':department_id' => $user->department_id,
                ':course_id' => $user->course_id,
                ':year_level_id' => $user->year_level_id,
                ':designation_id' => $user->designation_id,
                ':id_expiration_date' => $user->id_expiration_date,
                ':created_at' => $user->created_at ? $user->created_at->toDateTimeString() : null,
                ':updated_at' => $user->updated_at ? $user->updated_at->toDateTimeString() : null,
            ]);
        }

        // Attendance Logs
        $logs = AttendanceLog::all();
        $stmtLog = $pdo->prepare("
            INSERT INTO attendance_logs (
                id, user_id, scan_type, entry_type, scanned_at, created_at, updated_at, sync_status
            ) VALUES (
                :id, :user_id, :scan_type, :entry_type, :scanned_at, :created_at, :updated_at, 1
            )
        ");

        foreach ($logs as $log) {
            $stmtLog->execute([
                ':id' => $log->id,
                ':user_id' => $log->user_id,
                ':scan_type' => $log->scan_type,
                ':entry_type' => $log->entry_type,
                ':scanned_at' => $log->scanned_at,
                ':created_at' => $log->created_at ? $log->created_at->toDateTimeString() : null,
                ':updated_at' => $log->updated_at ? $log->updated_at->toDateTimeString() : null,
            ]);
        }

        return response()->download($tempDbPath, 'library_app_db.sqlite', [
            'Content-Type' => 'application/x-sqlite3'
        ])->deleteFileAfterSend(true);
    }
}
