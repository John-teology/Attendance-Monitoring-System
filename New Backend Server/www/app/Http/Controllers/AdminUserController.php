<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Shuchkin\SimpleXLSX;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // AUTO-CLEANUP: Deactivate expired users automatically when list is viewed
        User::where('status', 'active')
            ->whereNotNull('id_expiration_date')
            ->whereDate('id_expiration_date', '<', now()->toDateString())
            ->update(['status' => 'inactive']);

        $query = User::with(['department', 'course', 'designation', 'yearLevel']);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('user_type') && $request->user_type != '') {
            $query->where('user_type', $request->user_type);
        }
        
        if ($request->has('department') && $request->department != '') {
            $query->where('department_id', $request->department);
        }
        
        if ($request->has('year_level') && $request->year_level != '') {
            $query->where('year_level_id', $request->year_level);
        }

        $users = $query->paginate(10);

        if ($request->ajax() && !$request->header('X-SPA-REQUEST')) {
            return view('admin.users.partials.table', compact('users'))->render();
        }

        // Fetch Lookup Data (ID and Name)
        $departments = DB::table('departments')->orderBy('name')->get();
        $courses = DB::table('courses')->orderBy('name')->get();
        $designations = DB::table('designations')->orderBy('name')->get();
        $yearLevels = DB::table('year_levels')->orderBy('name')->get();

        $skeletonUsers = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
        return view('admin.users.index', [
            'users' => $skeletonUsers,
            'departments' => $departments,
            'courses' => $courses,
            'designations' => $designations,
            'yearLevels' => $yearLevels
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $idNumber = trim((string) $request->input('id_number', ''));
        $rfidUid = trim((string) $request->input('rfid_uid', ''));

        $request->merge([
            'id_number' => $idNumber,
            'qr_code' => $idNumber,
            'rfid_uid' => $rfidUid === '' ? $idNumber : $rfidUid,
        ]);

        $request->validate([
            'user_type' => 'required|in:student,faculty',
            'full_name' => 'required|string|max:255',
            'id_number' => 'required|string|unique:users,id_number',
            'qr_code' => 'required|string|unique:users,qr_code',
            'rfid_uid' => 'required|string|unique:users,rfid_uid',
            'status' => 'required|in:active,inactive',
            'department_id' => 'nullable|exists:departments,id',
            'course_id' => 'nullable|required_if:user_type,student|exists:courses,id',
            'year_level_id' => 'nullable|required_if:user_type,student|exists:year_levels,id',
            'designation_id' => 'nullable|required_if:user_type,faculty|exists:designations,id',
            'id_expiration_date' => 'nullable|date',
        ]);

        $data = $request->all();
        
        // Auto-deactivate if expired date is set and passed
        if (!empty($data['id_expiration_date']) && \Carbon\Carbon::parse($data['id_expiration_date'])->isPast()) {
            $data['status'] = 'inactive';
        }

        $user = User::create($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'redirect_url' => route('admin.users.index')
            ]);
        }

        return redirect()->route('admin.users.index')
                         ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'user' => $user
            ]);
        }

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'user_type' => 'required|in:student,faculty',
            'full_name' => 'required|string|max:255',
            'id_number' => 'required|string|unique:users,id_number,'.$user->id,
            // qr_code is managed automatically
            'rfid_uid' => 'nullable|string|unique:users,rfid_uid,'.$user->id,
            'status' => 'required|in:active,inactive',
            'department_id' => 'nullable|exists:departments,id',
            'course_id' => 'nullable|required_if:user_type,student|exists:courses,id',
            'year_level_id' => 'nullable|required_if:user_type,student|exists:year_levels,id',
            'designation_id' => 'nullable|required_if:user_type,faculty|exists:designations,id',
            'id_expiration_date' => 'nullable|date',
            'profile_picture' => 'nullable|image|max:5120', // Max 5MB
        ]);

        $data = $request->except(['profile_picture', 'remove_profile_picture']);
        
        // Handle Profile Picture Removal
        if ($request->input('remove_profile_picture') == '1') {
            if ($user->profile_picture && file_exists(storage_path('app/public/' . $user->profile_picture))) {
                @unlink(storage_path('app/public/' . $user->profile_picture));
            }
            $data['profile_picture'] = null;
        }

        // Handle Profile Picture Upload
        if ($request->hasFile('profile_picture')) {
            // Delete old photo if exists
            if ($user->profile_picture && file_exists(storage_path('app/public/' . $user->profile_picture))) {
                @unlink(storage_path('app/public/' . $user->profile_picture));
            }

            // Save new photo
            $file = $request->file('profile_picture');
            $fileName = 'photos/' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Ensure directory exists in public/storage first (Symlink Target)
            if (!file_exists(storage_path('app/public/photos'))) {
                mkdir(storage_path('app/public/photos'), 0777, true);
            }

            // Store explicitly to public disk
            $file->storeAs('photos', basename($fileName), 'public');
            
            $data['profile_picture'] = $fileName;
        }

        // Auto-deactivate if expired date is set and passed
        if (!empty($data['id_expiration_date']) && \Carbon\Carbon::parse($data['id_expiration_date'])->isPast()) {
            $data['status'] = 'inactive';
        }
        
        // Handle Empty RFID
        if (empty($data['rfid_uid'])) {
            $data['rfid_uid'] = null; // Ensure it's null, not empty string
        }

        $user->update($data);
        
        // Ensure QR code matches ID Number
        if ($user->qr_code != $user->id_number) {
            $user->update(['qr_code' => $user->id_number]);
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'redirect_url' => route('admin.users.index')
            ]);
        }

        return redirect()->route('admin.users.index')
                         ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);
        }

        return redirect()->route('admin.users.index')
                         ->with('success', 'User deleted successfully.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No items selected.'], 400);
        }

        User::whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => count($ids) . ' users deleted successfully.'
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|max:5120', // Removed mimes check to avoid fileinfo error
        ]);

        // Manual extension check
        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt', 'xlsx', 'xls'])) {
            return response()->json(['success' => false, 'message' => 'Invalid file format. Allowed: csv, txt, xlsx, xls'], 422);
        }

        try {
            $file = $request->file('file');
            
            // Use SimpleXLSX for .xlsx files (No ZipArchive required)
            if ($extension == 'xlsx') {
                if ($xlsx = SimpleXLSX::parse($file->getPathname())) {
                    $rows = $xlsx->rows();
                } else {
                    throw new \Exception(SimpleXLSX::parseError());
                }
            } else {
                // Fallback for CSV
                $rows = array_map('str_getcsv', file($file->getRealPath()));
            }

            $header = array_shift($rows); // Get header row

            // Normalize header for easier matching
            $header = array_map(function($h) {
                return strtolower(trim($h ?? ''));
            }, $header);

            $count = 0; // Total processed
            $createdCount = 0;
            $updatedCount = 0;

            // Pre-fetch lookups for performance (Name => ID)
            $departments = DB::table('departments')->get()->mapWithKeys(fn($i) => [strtolower($i->name) => $i->id]);
            $courses = DB::table('courses')->get()->mapWithKeys(fn($i) => [strtolower($i->name) => $i->id]);
            $designations = DB::table('designations')->get()->mapWithKeys(fn($i) => [strtolower($i->name) => $i->id]);
            $yearLevels = DB::table('year_levels')->get()->mapWithKeys(fn($i) => [strtolower($i->name) => $i->id]);

            foreach ($rows as $row) {
                // Skip empty rows
                if (empty(array_filter($row))) continue;

                // Helper to get value by keys
                $getVal = function($keys) use ($header, $row) {
                    foreach ($keys as $k) {
                        // Exact match
                        $index = array_search(strtolower($k), $header);
                        if ($index !== false && isset($row[$index])) return trim($row[$index]);
                        
                        // Partial match
                        foreach($header as $i => $h) {
                            if (str_contains($h, strtolower($k))) return trim($row[$i] ?? '');
                        }
                    }
                    return null;
                };

                $fullName = $getVal(['full name', 'fullname']);
                $idNumber = $getVal(['id number', 'id_number']);
                
                if (!$fullName || !$idNumber) continue;

                // Determine User Type
                $userType = 'student'; // Default
                $designationName = $getVal(['designation']);
                $courseName = $getVal(['course']);
                $yearName = $getVal(['year', 'level']);
                $deptName = $getVal(['department']);
                
                if ($designationName && !$courseName) {
                    $userType = 'faculty';
                }
                // Override if explicit type column exists
                if ($val = $getVal(['user_type', 'type'])) {
                    $userType = strtolower($val);
                }

                // Resolve IDs
                $deptId = $departments[strtolower($deptName ?? '')] ?? null;
                $courseId = $courses[strtolower($courseName ?? '')] ?? null;
                $desigId = $designations[strtolower($designationName ?? '')] ?? null;
                $yearId = $yearLevels[strtolower($yearName ?? '')] ?? null;

                // Optional: Auto-create lookups if missing? For now, leave null.

                $status = strtolower($getVal(['status']) ?? 'active');
                $expDate = $getVal(['expiration', 'expire']);
                
                // Handle Date Parsing
                if ($expDate) {
                    try {
                        if (is_numeric($expDate)) {
                            $expDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($expDate)->format('Y-m-d');
                        } else {
                            $expDate = \Carbon\Carbon::parse($expDate)->format('Y-m-d');
                        }

                        if (\Carbon\Carbon::parse($expDate)->isPast()) {
                            $status = 'inactive';
                        }
                    } catch (\Exception $e) {
                        $expDate = null;
                    }
                }

                $user = User::updateOrCreate(
                    ['id_number' => $idNumber],
                    [
                        'user_type' => $userType,
                        'full_name' => $fullName,
                        'department_id' => $deptId,
                        'course_id' => $courseId,
                        'year_level_id' => $yearId,
                        'designation_id' => $desigId,
                        'id_expiration_date' => $expDate,
                        // Set RFID to ID Number if empty (Default behavior)
                        'rfid_uid' => $getVal(['rfid', 'uid']) ?: $idNumber,
                        'status' => $status,
                    ]
                );
                
                $user->update(['qr_code' => $user->id_number]);
                $count++;
                
                if ($user->wasRecentlyCreated) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }
            }

            $message = "Processed $count users: $createdCount created, $updatedCount updated.";

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect_url' => route('admin.users.index')
                ]);
            }

            return redirect()->route('admin.users.index')
                             ->with('success', $message);

        } catch (\Exception $e) {
            Log::error($e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error importing file: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function uploadPhotos(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:zip|max:51200', // Max 50MB
        ]);

        $zipFile = $request->file('file');
        $zip = new \ZipArchive();
        
        if ($zip->open($zipFile->getPathname()) !== true) {
            return response()->json(['success' => false, 'message' => 'Unable to open ZIP file.'], 400);
        }

        $extractPath = storage_path('app/tmp/photos_upload_' . uniqid());
        if (!is_dir($extractPath)) {
            mkdir($extractPath, 0777, true);
        }

        $zip->extractTo($extractPath);
        $zip->close();

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $count = 0;
        $errors = 0;

        foreach ($files as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $identifier = $file->getBasename('.' . $file->getExtension());
                    
                    // Find user by RFID UID or ID Number
                    $user = User::where('rfid_uid', $identifier)
                                ->orWhere('id_number', $identifier)
                                ->first();
                    
                    if ($user) {
                        // Move file to public storage
                        $fileName = 'photos/' . $user->id . '_' . time() . '.' . $extension;
                        // Ensure directory exists
                        $destDir = storage_path('app/public/photos');
                        if (!is_dir($destDir)) {
                            mkdir($destDir, 0777, true);
                        }
                        
                        // Copy file
                        if (copy($file->getRealPath(), storage_path('app/public/' . $fileName))) {
                            // Update user record
                            // Delete old photo if exists
                            if ($user->profile_picture && file_exists(storage_path('app/public/' . $user->profile_picture))) {
                                @unlink(storage_path('app/public/' . $user->profile_picture));
                            }
                            
                            $user->update(['profile_picture' => $fileName]);
                            $count++;
                        } else {
                            $errors++;
                        }
                    }
                }
            }
        }

        // Cleanup
        $this->recursiveDelete($extractPath);

        return response()->json([
            'success' => true,
            'message' => "Processed photos. Updated $count users. ($errors errors)"
        ]);
    }

    private function recursiveDelete($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveDelete("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public function downloadQr($id)
    {
        $user = User::with(['course', 'designation'])->findOrFail($id);
        
        // Ensure QR matches ID Number
        if ($user->qr_code != $user->id_number) {
            $user->update(['qr_code' => $user->id_number]);
        }

        $value = $user->qr_code;
        $info = $user->user_type === 'student'
            ? ($user->course->name ?? '')
            : ($user->designation->name ?? '');

        // Generate QR as Data URI
        $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode(
            QrCode::format('svg')->size(200)->margin(1)->generate($value)
        );

        $card = [
            'full_name' => $user->full_name,
            'id_number' => $user->id_number,
            'info' => $info,
            'qr_data_uri' => $qrDataUri
        ];

        // Generate PDF
        $pdf = Pdf::loadView('admin.users.single-qr', compact('card'));
        $pdf->setPaper('a4', 'portrait');
        
        $filename = 'qr_' . $user->id_number . '.pdf';
        
        return $pdf->download($filename);
    }

    public function exportQrPdf(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $query = User::query()
            ->select(['id', 'full_name', 'id_number', 'user_type', 'course_id', 'designation_id', 'qr_code'])
            ->with([
                'course:id,name',
                'designation:id,name',
            ]);

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('user_type')) {
            $query->where('user_type', $request->get('user_type'));
        }

        if ($request->filled('department')) {
            $query->where('department_id', $request->get('department'));
        }

        if ($request->filled('year_level')) {
            $query->where('year_level_id', $request->get('year_level'));
        }

        $chunkSize = 300;

        $baseName = 'user_qr_codes_' . date('Y-m-d_H-i-s');
        $zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $baseName . '.zip';
        $zip = new \ZipArchive();
        $opened = $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        if ($opened !== true) {
            abort(500, 'Unable to create ZIP file.');
        }

        $part = 1;
        
        $query->orderBy('id')->chunkById($chunkSize, function($users) use (&$part, $zip) {
            $cards = $users->map(function($user) {
                $value = $user->qr_code ?: $user->id_number;
                $info = $user->user_type === 'student'
                    ? ($user->course->name ?? '')
                    : ($user->designation->name ?? '');

                return [
                    'full_name' => $user->full_name,
                    'id_number' => $user->id_number,
                    'info' => $info,
                    'qr_data_uri' => 'data:image/svg+xml;base64,' . base64_encode(
                        QrCode::format('svg')->size(140)->margin(1)->generate($value)
                    ),
                ];
            });

            $pdf = Pdf::loadView('admin.users.qr-pdf', ['cards' => $cards]);
            $pdf->setPaper('a4', 'portrait');

            $pdfName = 'user_qr_codes_part_' . str_pad((string)$part, 3, '0', STR_PAD_LEFT) . '.pdf';
            $pdfContent = $pdf->output();
            $zip->addFromString($pdfName, $pdfContent);
            $part++;
        });

        $zip->close();

        return response()->download($zipPath, $baseName . '.zip', [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    public function export(Request $request)
    {
        $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['ID Number', 'Full Name', 'Type', 'Department', 'Course', 'Year Level', 'Designation', 'Status', 'Expiration Date', 'RFID UID'];

        $callback = function() use ($request, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            $query = User::with(['department', 'course', 'designation', 'yearLevel']);

            // Apply same filters as index
            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                      ->orWhere('id_number', 'like', "%{$search}%");
                });
            }

            if ($request->has('user_type') && $request->user_type != '') {
                $query->where('user_type', $request->user_type);
            }
            
            if ($request->has('department') && $request->department != '') {
                $query->where('department_id', $request->department);
            }
            
            if ($request->has('year_level') && $request->year_level != '') {
                $query->where('year_level_id', $request->year_level);
            }

            $query->chunk(100, function($users) use ($file) {
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->id_number,
                        $user->full_name,
                        $user->user_type,
                        $user->department->name ?? '',
                        $user->course->name ?? '',
                        $user->yearLevel->name ?? '',
                        $user->designation->name ?? '',
                        $user->status,
                        $user->id_expiration_date,
                        $user->rfid_uid
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download Excel Template for Import with Dropdowns
     */
    public function downloadTemplate(Request $request)
    {
        $type = $request->query('type', 'student');
        $filename = "{$type}_import_template.xlsx";

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        // 1. Set Headers
        $headers = ['Full Name', 'ID Number', 'RFID UID', 'Department'];
        if ($type == 'student') {
            $headers[] = 'Course';
            $headers[] = 'Year Level';
        } else {
            $headers[] = 'Designation';
        }
        $headers = array_merge($headers, ['ID Expiration Date (YYYY-MM-DD)', 'Status']);

        $sheet->fromArray($headers, NULL, 'A1');
        
        // Auto-size columns
        foreach (range('A', chr(65 + count($headers) - 1)) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 2. Create Reference Sheet for Dropdowns
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Reference');
        // $refSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN); // Make visible per request

        // Add Headers for Reference Sheet
        $refSheet->setCellValue('A1', 'Departments List');
        $refSheet->setCellValue('B1', $type == 'student' ? 'Courses List' : 'Designations List');
        if ($type == 'student') $refSheet->setCellValue('C1', 'Year Levels List');
        $refSheet->setCellValue($type == 'student' ? 'D1' : 'C1', 'Status List');
        $refSheet->getStyle('A1:D1')->getFont()->setBold(true);

        // Populate Departments (Start from Row 2)
        $departments = DB::table('departments')->orderBy('name')->pluck('name')->toArray();
        if (empty($departments)) $departments = ['No Departments Found'];
        $refSheet->fromArray(array_map(fn($v) => [$v], $departments), NULL, 'A2');
        $deptCount = count($departments) + 1;
        $spreadsheet->addNamedRange(new NamedRange('Departments', $refSheet, "A2:A$deptCount"));

        // Populate Courses or Designations
        if ($type == 'student') {
            $courses = DB::table('courses')->orderBy('name')->pluck('name')->toArray();
            if (empty($courses)) $courses = ['No Courses Found'];
            $refSheet->fromArray(array_map(fn($v) => [$v], $courses), NULL, 'B2');
            $courseCount = count($courses) + 1;
            $spreadsheet->addNamedRange(new NamedRange('Courses', $refSheet, "B2:B$courseCount"));

            // Year Levels
            $years = DB::table('year_levels')->orderBy('name')->pluck('name')->toArray();
            if (empty($years)) $years = ['1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year'];
            
            $refSheet->fromArray(array_map(fn($v) => [$v], $years), NULL, 'C2');
            $yearCount = count($years) + 1;
            $spreadsheet->addNamedRange(new NamedRange('Years', $refSheet, "C2:C$yearCount"));
            
            $statusCol = 'D';
        } else {
            $designations = DB::table('designations')->orderBy('name')->pluck('name')->toArray();
            if (empty($designations)) $designations = ['No Designations Found'];
            $refSheet->fromArray(array_map(fn($v) => [$v], $designations), NULL, 'B2');
            $desigCount = count($designations) + 1;
            $spreadsheet->addNamedRange(new NamedRange('Designations', $refSheet, "B2:B$desigCount"));
            
            $statusCol = 'C';
        }

        // Status
        $statuses = ['active', 'inactive'];
        $refSheet->fromArray(array_map(fn($v) => [$v], $statuses), NULL, "$statusCol" . "2");
        $spreadsheet->addNamedRange(new NamedRange('Statuses', $refSheet, "$statusCol" . "2:$statusCol" . "3"));
        
        // Auto-size Reference Columns
        foreach (range('A', 'D') as $col) {
            $refSheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 3. (Removed) Data Validation / Dropdowns
        // The Reference sheet serves as a guide, but input cells are free text.

        // 4. Output
        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename);
    }
}
