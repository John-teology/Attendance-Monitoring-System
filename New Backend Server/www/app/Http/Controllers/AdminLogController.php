<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. Data Fetch Request (AJAX) - Return actual data
        if ($request->ajax() && !$request->header('X-SPA-REQUEST')) {
            $logs = $this->getFilteredQuery($request)->paginate(20)->withQueryString();
            return view('admin.logs.partials.table', compact('logs'))->render();
        }

        // 2. Initial Page Load - Return ALL data (paginated)
        $logs = $this->getFilteredQuery($request)->paginate(20)->withQueryString();
        $stats = [
            'total_count' => AttendanceLog::count(),
        ];
        
        $departments = DB::table('departments')->orderBy('name')->get();
        $courses = DB::table('courses')->orderBy('name')->get();
        $yearLevels = DB::table('year_levels')->orderBy('name')->get();
        $terms = Term::with('yearLevels')->orderBy('academic_year', 'desc')->orderBy('start_date', 'asc')->get();
        
        return view('admin.logs.index', compact('logs', 'stats', 'departments', 'courses', 'yearLevels', 'terms'));
    }

    public function export(Request $request)
    {
        $logs = $this->getFilteredQuery($request)->get();
        $csvFileName = 'attendance_logs_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date/Time', 'User Name', 'ID Number', 'User Type', 'Department', 'Course', 'Year Level', 'Term', 'Student ID', 'Enrollment AY', 'Enrollment Year', 'Enrollment Course', 'Enrollment Term Type', 'Scan Type', 'Entry Type']);

            $placeholder = 'No Data Available';

            if ($logs->isEmpty()) {
                // Return a single row indicating no data if the result set is empty
                fputcsv($file, [$placeholder, '', '', '', '', '', '', '', '']);
            } else {
                foreach ($logs as $log) {
                    $date = Carbon::createFromTimestampMs($log->scanned_at)->timezone(config('app.timezone'))->toDateTimeString();
                    $termText = $log->term ? ($log->term->academic_year . ' ' . ucfirst($log->term->type) . ' ' . $log->term->name) : $placeholder;
                    $termText = $log->term ? ($log->term->academic_year . ' ' . ucfirst($log->term->type) . ' ' . $log->term->name) : $placeholder;
                    $enrollYear = $log->enrollment ? $log->enrollment->academic_year : '';
                    $enrollYl = $log->enrollment && $log->enrollment->yearLevel ? $log->enrollment->yearLevel->name : '';
                    $enrollCourse = $log->enrollment && $log->enrollment->course ? $log->enrollment->course->name : '';
                    $enrollType = $log->enrollment ? ucfirst($log->enrollment->term_type) : '';
                    fputcsv($file, [
                        $date,
                        $log->user ? $log->user->full_name : $placeholder,
                        $log->user ? $log->user->id_number : $placeholder,
                        $log->user ? $log->user->user_type : $placeholder,
                        $log->user && $log->user->department ? $log->user->department->name : $placeholder,
                        $log->user && $log->user->course ? $log->user->course->name : $placeholder,
                        $log->user && $log->user->yearLevel ? $log->user->yearLevel->name : $placeholder,
                        $termText,
                        $log->student ? $log->student->student_number : '',
                        $enrollYear,
                        $enrollYl,
                        $enrollCourse,
                        $enrollType,
                        $log->scan_type,
                        $log->entry_type
                    ]);
                }
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getFilteredQuery(Request $request)
    {
        // Order by Created At (Last Entry First) as requested
        $query = AttendanceLog::with(['user.department', 'user.course', 'user.yearLevel', 'term', 'student', 'enrollment.yearLevel', 'enrollment.course'])
                              ->orderBy('created_at', 'desc');

        if ($request->has('date') && $request->date != '') {
            $date = Carbon::parse($request->date);
            // scanned_at is a timestamp in ms, need to convert or query range
            // Assuming scanned_at is stored as BIGINT timestamp in milliseconds
            $start = $date->startOfDay()->timestamp * 1000;
            $end = $date->endOfDay()->timestamp * 1000;
            $query->whereBetween('scanned_at', [$start, $end]);
        }

        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        // Advanced Filters
        if ($request->has('user_type') && $request->user_type != '') {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('user_type', $request->user_type);
            });
        }
        if ($request->has('department') && $request->department != '') {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('department_id', $request->department);
            });
        }
        if ($request->has('course') && $request->course != '') {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('course_id', $request->course);
            });
        }
        if ($request->has('year_level') && $request->year_level != '') {
            $query->whereHas('user', function($q) use ($request) {
                $q->where('year_level_id', $request->year_level);
            });
        }

        if ($request->has('student_number') && $request->student_number != '') {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('student_number', $request->student_number);
            });
        }

        if ($request->has('enrollment_id') && $request->enrollment_id != '') {
            $query->where('enrollment_id', $request->enrollment_id);
        }

        if ($request->has('term') && $request->term != '') {
            $query->where('term_id', $request->term);
        }

        return $query;
    }

    public function destroy($id)
    {
        $log = AttendanceLog::findOrFail($id);
        $log->delete();

        return redirect()->route('admin.reports.index')->with('success', 'Attendance log deleted successfully.');
    }

    public function purge(Request $request)
    {
        AttendanceLog::query()->delete();
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'All attendance logs have been deleted.']);
        }
        return redirect()->route('admin.reports.index')->with('success', 'All attendance logs have been deleted.');
    }
}
