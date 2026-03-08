<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Course;
use App\Models\Designation;
use App\Models\Term;
use App\Models\YearLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Shuchkin\SimpleXLSX;

class AdminLookupController extends Controller
{
    private function getModel($type)
    {
        return match ($type) {
            'department' => Department::class,
            'course' => Course::class,
            'designation' => Designation::class,
            'year_level' => YearLevel::class,
            default => null,
        };
    }

    public function index()
    {
        return view('admin.lookups.index', [
            'departments' => Department::orderBy('name')->get(),
            'courses' => Course::orderBy('name')->get(),
            'designations' => Designation::orderBy('name')->get(),
            'year_levels' => YearLevel::orderBy('name')->get(),
            'terms' => Term::with('yearLevels')->orderBy('academic_year', 'desc')->orderBy('start_date')->get(),
        ]);
    }

    public function store(Request $request, $type)
    {
        $modelClass = $this->getModel($type);
        if (!$modelClass) abort(404);

        $rules = ['name' => 'required|string|unique:' . (new $modelClass)->getTable() . ',name'];
        if ($type === 'year_level') {
            $rules['term_type'] = 'nullable|in:semestral,quarteral';
        }
        $request->validate($rules);

        $payload = ['name' => $request->name];
        if ($type === 'year_level' && $request->filled('term_type')) {
            $payload['term_type'] = $request->term_type;
        }
        $modelClass::create($payload);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Item added successfully.']);
        }

        return redirect()->back()->with('success', 'Item added successfully.');
    }

    public function update(Request $request, $type, $id)
    {
        $modelClass = $this->getModel($type);
        if (!$modelClass) abort(404);

        $model = $modelClass::findOrFail($id);

        $rules = ['name' => 'required|string|unique:' . (new $modelClass)->getTable() . ',name,' . $id];
        if ($type === 'year_level') {
            $rules['term_type'] = 'nullable|in:semestral,quarteral';
        }
        $request->validate($rules);

        $payload = ['name' => $request->name];
        if ($type === 'year_level' && $request->filled('term_type')) {
            $payload['term_type'] = $request->term_type;
        }
        $model->update($payload);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Item updated successfully.']);
        }

        return redirect()->back()->with('success', 'Item updated successfully.');
    }

    public function destroy(Request $request, $type, $id)
    {
        $modelClass = $this->getModel($type);
        if (!$modelClass) abort(404);

        // Check if in use
        $column = match($type) {
            'department' => 'department_id',
            'course' => 'course_id',
            'designation' => 'designation_id',
            'year_level' => 'year_level_id',
            default => null,
        };

        if ($column && \App\Models\User::where($column, $id)->exists()) {
            $msg = 'Cannot delete this item because it is assigned to one or more users.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }
            return redirect()->back()->with('error', $msg);
        }

        $modelClass::findOrFail($id)->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Item deleted successfully.']);
        }

        return redirect()->back()->with('success', 'Item deleted successfully.');
    }

    public function bulkDestroy(Request $request, $type)
    {
        $modelClass = $this->getModel($type);
        if (!$modelClass) abort(404);

        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No items selected.'], 400);
        }

        // Check usage for all IDs
        $column = match($type) {
            'department' => 'department_id',
            'course' => 'course_id',
            'designation' => 'designation_id',
            'year_level' => 'year_level_id',
            default => null,
        };

        if ($column) {
            $usedCount = \App\Models\User::whereIn($column, $ids)->count();
            if ($usedCount > 0) {
                return response()->json([
                    'success' => false, 
                    'message' => "Cannot delete selected items because some are assigned to users."
                ], 400);
            }
        }

        $modelClass::whereIn('id', $ids)->delete();

        return response()->json([
            'success' => true,
            'message' => count($ids) . ' items deleted successfully.'
        ]);
    }

    public function import(Request $request, $type)
    {
        $modelClass = $this->getModel($type);
        if (!$modelClass) abort(404);

        $request->validate([
            'file' => 'required|max:2048', // Removed mimes check
        ]);

        // Manual extension check
        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        if (!in_array($extension, ['csv', 'txt', 'xlsx', 'xls'])) {
             if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Invalid file format. Allowed: csv, txt, xlsx, xls'], 422);
             }
             return redirect()->back()->with('error', 'Invalid file format.');
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

            // Assume Row 1 is header, data starts Row 2
            $count = 0;
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header
                if (empty($row[0])) continue;

                $name = trim($row[0]);
                $modelClass::firstOrCreate(['name' => $name]);
                $count++;
            }

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => "Imported $count items successfully."]);
            }

            return redirect()->back()->with('success', "Imported $count items successfully.");

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function downloadTemplate($type)
    {
        $modelClass = $this->getModel($type);
        if (!$modelClass) abort(404);

        $filename = "{$type}_template.xlsx";
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($type === 'year_level') {
            // Sheet 1: Year Levels
            $sheet->setTitle('Year Levels');
            $sheet->setCellValue('A1', 'Name');
            $sheet->setCellValue('B1', 'Term Type');
            $sheet->setCellValue('A2', '1st Year');
            $sheet->setCellValue('B2', 'semestral');
            $sheet->setCellValue('A3', 'Grade 1');
            $sheet->setCellValue('B3', 'quarteral');
            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);

            // Sheet 2: Term Types reference
            $ref = $spreadsheet->createSheet();
            $ref->setTitle('Term Types');
            $ref->setCellValue('A1', 'Valid Term Types');
            $ref->setCellValue('A2', 'semestral');
            $ref->setCellValue('A3', 'quarteral');
            $ref->getColumnDimension('A')->setAutoSize(true);
        } else {
            // Default single-sheet template
            $sheet->setTitle(ucfirst(str_replace('_', ' ', $type)));
            $sheet->setCellValue('A1', 'Name');
            $sheet->setCellValue('A2', 'Sample ' . ucfirst(str_replace('_', ' ', $type)));
            $sheet->getColumnDimension('A')->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename);
    }
}
