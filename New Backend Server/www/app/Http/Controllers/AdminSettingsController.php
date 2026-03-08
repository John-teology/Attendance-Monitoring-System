<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

class AdminSettingsController extends Controller
{
    private function generateDummyUsers(int $count): int
    {
        if ($count <= 0) {
            return 0;
        }

        $departments = DB::table('departments')->pluck('id')->all();
        $courses = DB::table('courses')->pluck('id')->all();
        $designations = DB::table('designations')->pluck('id')->all();
        $yearLevels = DB::table('year_levels')->pluck('id')->all();

        $maxIdNumber = (int) (DB::table('users')->selectRaw("MAX(CAST(id_number AS INTEGER)) as m")->value('m') ?? 10000000);

        $firstNames = [
            'Alex','Sam','Jordan','Taylor','Casey','Jamie','Morgan','Riley','Avery','Cameron',
            'Kai','Noah','Liam','Mia','Sophia','Emma','Olivia','Ethan','Lucas','Aiden'
        ];
        $lastNames = [
            'Santos','Reyes','Cruz','Garcia','Torres','Flores','Gonzales','Ramirez','Diaz','Hernandez',
            'Castillo','Rivera','Delos Santos','Mendoza','Navarro','Ramos','Bautista','Aquino','Villanueva','Pascual'
        ];
        $titles = ['Prof.','Dr.','Engr.','Mr.','Ms.'];

        $batchSize = 500;
        $now = now();
        $created = 0;

        for ($offset = 0; $offset < $count; $offset += $batchSize) {
            $limit = min($batchSize, $count - $offset);
            $rows = [];

            for ($i = 0; $i < $limit; $i++) {
                $seq = $maxIdNumber + $offset + $i + 1;
                $idNumber = str_pad((string) $seq, 8, '0', STR_PAD_LEFT);

                $isStudent = (random_int(0, 1) === 0);
                $userType = $isStudent ? 'student' : 'faculty';

                $first = $firstNames[array_rand($firstNames)];
                $last = $lastNames[array_rand($lastNames)];
                $fullName = ($isStudent ? '' : ($titles[array_rand($titles)] . ' ')) . $first . ' ' . $last;

                $departmentId = !empty($departments) ? $departments[array_rand($departments)] : null;
                $courseId = $isStudent && !empty($courses) ? $courses[array_rand($courses)] : null;
                $yearLevelId = $isStudent && !empty($yearLevels) ? $yearLevels[array_rand($yearLevels)] : null;
                $designationId = (!$isStudent) && !empty($designations) ? $designations[array_rand($designations)] : null;

                $rows[] = [
                    'user_type' => $userType,
                    'full_name' => $fullName,
                    'id_number' => $idNumber,
                    'qr_code' => $idNumber,
                    'rfid_uid' => null,
                    'status' => 'active',
                    'department_id' => $departmentId,
                    'course_id' => $courseId,
                    'year_level_id' => $yearLevelId,
                    'designation_id' => $designationId,
                    'id_expiration_date' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('users')->insert($rows);
            $created += count($rows);
        }

        return $created;
    }

    public function index()
    {
        // Hide super_admin from other admins
        if (auth('admin')->user()->role === 'super_admin') {
            $admins = Admin::all();
        } else {
            $admins = Admin::where('role', '!=', 'super_admin')->get();
        }
        
        return view('admin.settings.index', compact('admins'));
    }

    public function updateBranding(Request $request)
    {
        if (auth('admin')->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        // Check if POST is empty but Content-Length is not (indicates post_max_size exceeded)
        if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            return back()->withErrors(['school_logo' => 'The uploaded file is too large (exceeds post_max_size of ' . ini_get('post_max_size') . ').']);
        }

        // Check for PHP upload errors for School Logo
        if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] !== UPLOAD_ERR_OK && $_FILES['school_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
             $error = $_FILES['school_logo']['error'];
             $message = 'File upload error occurred.';
             if ($error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE) {
                 $message = 'The file is too large (exceeds ' . ini_get('upload_max_filesize') . ').';
             }
             return back()->withErrors(['school_logo' => $message]);
        }

        // Check for PHP upload errors for App Background
        if (isset($_FILES['app_background_image']) && $_FILES['app_background_image']['error'] !== UPLOAD_ERR_OK && $_FILES['app_background_image']['error'] !== UPLOAD_ERR_NO_FILE) {
             $error = $_FILES['app_background_image']['error'];
             $message = 'File upload error occurred.';
             if ($error == UPLOAD_ERR_INI_SIZE || $error == UPLOAD_ERR_FORM_SIZE) {
                 $message = 'The file is too large (exceeds ' . ini_get('upload_max_filesize') . ').';
             }
             return back()->withErrors(['app_background_image' => $message]);
        }

        // Validate without relying on mime-type guessing if fileinfo is missing
        $rules = [
            'school_name' => 'required|string|max:255',
            // Removed 'file' rule to avoid Finfo dependency in validation
            // We rely on manual $_FILES check above
            'school_logo' => 'nullable', 
            'app_background_image' => 'nullable',
            'school_name_color' => 'nullable|string|max:20',
            'button_bg_color' => 'nullable|string|max:20',
            'body_bg_color' => 'nullable|string|max:20',
            'font_style' => 'nullable|string|max:50',
            'card_transparency' => 'nullable|integer|min:0|max:100',
            'button_transparency' => 'nullable|integer|min:0|max:100',
            'icon_color' => 'nullable|string|max:20',
            'volume_level' => 'nullable|integer|min:0|max:100',
        ];

        // Only add mime type validation if fileinfo is loaded
        if (extension_loaded('fileinfo')) {
             $rules['school_logo'] = 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048';
             $rules['app_background_image'] = 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048';
        }
        
        $request->validate($rules);

        $settings = SystemSetting::firstOrNew();
        $settings->school_name = $request->school_name;
        $settings->school_name_color = $request->school_name_color;
        $settings->button_bg_color = $request->button_bg_color;
        $settings->body_bg_color = $request->body_bg_color;
        $settings->font_style = $request->font_style;
        $settings->card_transparency = $request->card_transparency ?? 80;
        $settings->button_transparency = $request->button_transparency ?? 100;
        $settings->icon_color = $request->icon_color;
        
        // Handle Volume: If 'enable_volume_control' is present, save volume_level. Else set to null.
        if ($request->has('enable_volume_control')) {
            $settings->volume_level = $request->volume_level ?? 80;
        } else {
            $settings->volume_level = null;
        }

        // Handle Removals
        if ($request->has('remove_school_logo') && $settings->school_logo) {
            $oldPath = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $settings->school_logo));
            if (file_exists($oldPath)) @unlink($oldPath);
            $settings->school_logo = null;
        }
        
        if ($request->has('remove_app_background_image') && $settings->app_background_image) {
            $oldPath = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $settings->app_background_image));
            if (file_exists($oldPath)) @unlink($oldPath);
            $settings->app_background_image = null;
        }

        // Force check $_FILES if Laravel missed it
        $hasFile = $request->hasFile('school_logo');
        if (!$hasFile && isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK && !empty($_FILES['school_logo']['name'])) {
            $hasFile = true;
        }

        if ($hasFile) {
            // Delete old logo if exists using native PHP to avoid Finfo crash in Storage facade
            if ($settings->school_logo) {
                $oldPath = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $settings->school_logo));
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            
            // Manual check for file extension if fileinfo is missing
            $extension = '';
            if ($request->hasFile('school_logo')) {
                $extension = strtolower($request->file('school_logo')->getClientOriginalExtension());
            } else {
                 $parts = explode('.', $_FILES['school_logo']['name']);
                 $extension = strtolower(end($parts));
            }
            
            if (!extension_loaded('fileinfo')) {
                if (!in_array($extension, ['png', 'jpg', 'jpeg', 'svg'])) {
                    return back()->withErrors(['school_logo' => 'The school logo must be a file of type: png, jpg, jpeg, svg.']);
                }
            }
            
            // Generate a filename manually
            $filename = uniqid('branding_') . '.' . $extension;
            
            // Manually move the file to bypass all internal MIME checking in Laravel's Storage
            // Get the public storage path and ensure correct separators
            $destinationPath = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'branding');
            
            // Create directory if it doesn't exist
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            // Move the file
            if ($request->hasFile('school_logo')) {
                $request->file('school_logo')->move($destinationPath, $filename);
            } else {
                move_uploaded_file($_FILES['school_logo']['tmp_name'], $destinationPath . DIRECTORY_SEPARATOR . $filename);
            }
            
            // Double check if file exists after move
            if (!file_exists($destinationPath . DIRECTORY_SEPARATOR . $filename)) {
                // Try copy as fallback if original still exists
                if ($request->hasFile('school_logo')) {
                     copy($request->file('school_logo')->getPathname(), $destinationPath . DIRECTORY_SEPARATOR . $filename);
                } else {
                     copy($_FILES['school_logo']['tmp_name'], $destinationPath . DIRECTORY_SEPARATOR . $filename);
                }
            }
            
            // Set the path relative to public disk
            // Force forward slashes for URL compatibility
            $path = 'branding/' . $filename;
            
            $settings->school_logo = $path;
        }

        // Handle App Background Image
        $hasBg = $request->hasFile('app_background_image');
        if (!$hasBg && isset($_FILES['app_background_image']) && $_FILES['app_background_image']['error'] === UPLOAD_ERR_OK && !empty($_FILES['app_background_image']['name'])) {
            $hasBg = true;
        }

        if ($hasBg) {
            // Delete old
            if ($settings->app_background_image) {
                $oldPath = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $settings->app_background_image));
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            
            // Manual check for file extension
            $extension = '';
            if ($request->hasFile('app_background_image')) {
                $extension = strtolower($request->file('app_background_image')->getClientOriginalExtension());
            } else {
                 $parts = explode('.', $_FILES['app_background_image']['name']);
                 $extension = strtolower(end($parts));
            }
            
            if (!extension_loaded('fileinfo')) {
                if (!in_array($extension, ['png', 'jpg', 'jpeg', 'svg'])) {
                    return back()->withErrors(['app_background_image' => 'The background image must be a file of type: png, jpg, jpeg, svg.']);
                }
            }
            
            $filename = uniqid('bg_') . '.' . $extension;
            $destinationPath = storage_path('app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'branding');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            
            if ($request->hasFile('app_background_image')) {
                $request->file('app_background_image')->move($destinationPath, $filename);
            } else {
                move_uploaded_file($_FILES['app_background_image']['tmp_name'], $destinationPath . DIRECTORY_SEPARATOR . $filename);
            }
            
            // Double check if file exists after move
            if (!file_exists($destinationPath . DIRECTORY_SEPARATOR . $filename)) {
                if ($request->hasFile('app_background_image')) {
                     copy($request->file('app_background_image')->getPathname(), $destinationPath . DIRECTORY_SEPARATOR . $filename);
                } else {
                     copy($_FILES['app_background_image']['tmp_name'], $destinationPath . DIRECTORY_SEPARATOR . $filename);
                }
            }
            
            $path = 'branding/' . $filename;
            $settings->app_background_image = $path;
        }

        $settings->save();

        return redirect()->route('admin.settings.index')->with('success', 'System branding updated successfully.');
    }

    public function store(Request $request)
    {
        // Enforce: Admin-created users are always Admin
        if (auth('admin')->user()->role !== 'super_admin') {
            // Only super_admin can assign roles freely.
            // If the current user is NOT super_admin, we might restrict them
            // but currently the UI is only accessible to super_admin anyway for creating admins?
            // Wait, per the new requirement, maybe 'admin' role can create users?
            // If so, we need to respect the role they picked, unless it's super_admin.
            
            // However, the issue is likely this line forcing 'admin' role:
            // $request->merge(['role' => 'admin']); 
            // This overrides whatever the user selected.
            
            // Let's remove the override, but still prevent them from creating super_admin.
            if ($request->role === 'super_admin') {
                abort(403, 'Unauthorized action.');
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,editor,viewer',
        ]);

        // Prevent creating super_admin role via UI
        if ($request->role === 'super_admin') {
             return redirect()->back()->with('error', 'Cannot create Super Admin users manually.');
        }

        Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect()->route('admin.settings.index')->with('success', 'Admin user created successfully.');
    }

    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $admin = Admin::findOrFail($id);
        
        $admin->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.settings.index')->with('success', 'Admin password updated successfully.');
    }

    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);
        
        // Only admins and super_admin can update; middleware already enforces this
        // Non-super admins should not be able to modify the super_admin account
        if (auth('admin')->user()->role !== 'super_admin' && $admin->role === 'super_admin') {
            abort(403, 'Unauthorized action.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins,email,' . $admin->id,
        ]);
        
        $admin->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);
        
        return redirect()->route('admin.settings.index')->with('success', 'Admin profile updated successfully.');
    }

    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);
        
        // Prevent deleting self
        if (auth('admin')->id() == $admin->id) {
             return redirect()->route('admin.settings.index')->with('error', 'You cannot delete your own account.');
        }

        // Prevent deleting super_admin
        if ($admin->role === 'super_admin') {
            return redirect()->route('admin.settings.index')->with('error', 'The Super Admin account cannot be deleted.');
        }

        $admin->delete();
        return redirect()->route('admin.settings.index')->with('success', 'Admin user deleted successfully.');
    }

    public function generateDummyData(Request $request)
    {
        if (auth('admin')->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        if ($request->has('delete_all')) {
            $request->validate([
                'confirm_text' => 'required|in:DELETE ALL',
            ]);
            
            set_time_limit(0);
            
            try {
                // Delete all non-admin users (students/faculty)
                \App\Models\User::query()->delete();
                // Also delete all logs
                \App\Models\AttendanceLog::query()->delete();
                
                return redirect()->back()->with('success', "All user data and logs have been successfully deleted.");
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error deleting data: ' . $e->getMessage());
            }
        }
        
        // Handle "Fill Dashboard" Request
        if ($request->has('fill_dashboard')) {
            $request->validate(['confirm_text' => 'required|in:FILL']);
            set_time_limit(0);
            
            try {
                // 1. Create realistic users if count is low
                if (\App\Models\User::count() < 50) {
                    $this->generateDummyUsers(50);
                }
                
                // 2. Generate Attendance Logs for Today
                $users = \App\Models\User::all();
                $today = \Carbon\Carbon::today();
                $now = \Carbon\Carbon::now();
                
                $countLogs = 0;

                // Simulate traffic curve: Peak at 10 AM and 2 PM
                foreach ($users as $user) {
                    // Random chance to visit today (60%)
                    if (rand(1, 100) <= 60) {
                        // Generate Entry Time (7 AM to 5 PM)
                        $hour = rand(7, 16);
                        $minute = rand(0, 59);
                        
                        // Use Carbon to create object
                        $entryTime = $today->copy()->setHour($hour)->setMinute($minute);
                        
                        // FORCE LOG: Ignore future check if requested by user "I DONT CARE FILL THIS WITH LOGS"
                        // But wait, the Dashboard logic filters by "Today".
                        // If we log a future time (e.g. 2 PM when it is 10 AM), the dashboard WILL show it because it just checks date >= todayStart.
                        // So we CAN log future times for simulation purposes.
                        
                        // Create IN Log
                        \App\Models\AttendanceLog::create([
                            'user_id' => $user->id,
                            'entry_type' => 'IN',
                            'scan_type' => 'RFID',
                            'scanned_at' => $entryTime->timestamp * 1000
                        ]);
                        $countLogs++;
                        
                        // Random chance to be already OUT (80%)
                        if (rand(1, 100) <= 80) {
                            $durationMinutes = rand(15, 240); // 15 mins to 4 hours
                            $exitTime = $entryTime->copy()->addMinutes($durationMinutes);
                            
                            // Log OUT regardless of time
                            \App\Models\AttendanceLog::create([
                                'user_id' => $user->id,
                                'entry_type' => 'OUT',
                                'scan_type' => 'RFID',
                                'scanned_at' => $exitTime->timestamp * 1000
                            ]);
                            $countLogs++;
                        }
                    }
                }
                
                return redirect()->back()->with('success', "Dashboard populated with $countLogs logs from " . $users->count() . " users.");
                
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Error simulating dashboard: ' . $e->getMessage());
            }
        }

        $request->validate([
            'count' => 'required|integer|min:1|max:10000',
            'confirm_text' => 'required|in:CONFIRM',
        ]);

        $count = (int) $request->count;
        
        set_time_limit(0); // Allow long execution time

        try {
            $created = $this->generateDummyUsers($count);
            
            return redirect()->back()->with('success', "Successfully generated $created dummy users.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error generating data: ' . $e->getMessage());
        }
    }

    public function downloadDatabaseBackup()
    {
        $defaultConnection = config('database.default');
        $driver = config('database.connections.' . $defaultConnection . '.driver');

        if ($driver !== 'sqlite') {
            abort(400, 'Database backup is only supported for SQLite in this installer build.');
        }

        $sourcePath = database_path('database.sqlite');
        if (!file_exists($sourcePath)) {
            abort(404, 'Database file not found.');
        }

        $backupDir = storage_path('app' . DIRECTORY_SEPARATOR . 'backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $fileName = 'database_backup_' . now()->format('Y-m-d_H-i-s') . '.sqlite';
        $backupPath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

        try {
            if (file_exists($backupPath)) {
                @unlink($backupPath);
            }

            $escaped = str_replace("'", "''", $backupPath);
            DB::connection($defaultConnection)->getPdo()->exec("VACUUM INTO '{$escaped}'");

            if (!file_exists($backupPath) || filesize($backupPath) === 0) {
                throw new \RuntimeException('Backup file was not created.');
            }
        } catch (\Throwable $e) {
            DB::disconnect($defaultConnection);

            $copied = false;
            for ($i = 0; $i < 5; $i++) {
                if (@copy($sourcePath, $backupPath)) {
                    $copied = true;
                    break;
                }
                usleep(200000);
            }

            if (!$copied) {
                abort(500, 'Unable to create database backup.');
            }
        }

        // Encrypt the backup
        try {
            $content = file_get_contents($backupPath);
            $encrypted = Crypt::encrypt($content);
            file_put_contents($backupPath, $encrypted);
            // Rename to .enc to indicate encryption
            $encFileName = $fileName . '.enc';
            $encPath = $backupDir . DIRECTORY_SEPARATOR . $encFileName;
            rename($backupPath, $encPath);
            
            return response()
                ->download($encPath, $encFileName, ['Content-Type' => 'application/octet-stream'])
                ->deleteFileAfterSend(true);
        } catch (\Exception $e) {
             // Fallback to unencrypted if encryption fails (though it shouldn't)
             return response()
                ->download($backupPath, $fileName, ['Content-Type' => 'application/x-sqlite3'])
                ->deleteFileAfterSend(true);
        }
    }

    public function restoreDatabase(Request $request)
    {
        if (auth('admin')->user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'database_file' => 'required|file', 
        ]);

        $file = $request->file('database_file');
        $ext = strtolower($file->getClientOriginalExtension());
        
        // Decrypt if .enc
        $isEncrypted = ($ext === 'enc');
        
        // Basic extension check if not encrypted
        if (!$isEncrypted && $ext !== 'sqlite') {
             return back()->withErrors(['database_file' => 'The file must be a valid .sqlite or .enc backup file.']);
        }

        $targetPath = database_path('database.sqlite');
        
        try {
            DB::disconnect(config('database.default'));
        } catch (\Exception $e) {}

        try {
            if ($isEncrypted) {
                $content = file_get_contents($file->getRealPath());
                try {
                    $decrypted = Crypt::decrypt($content);
                    file_put_contents($targetPath, $decrypted);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    return back()->withErrors(['database_file' => 'Invalid backup file or encryption key mismatch.']);
                }
            } else {
                $file->move(database_path(), 'database.sqlite');
            }
            
            auth('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', 'Database restored successfully. Please log in again.');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to restore database: ' . $e->getMessage());
        }
    }
}
