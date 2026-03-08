<?php

namespace App\Http\Controllers;

use App\Models\Term;
use App\Models\YearLevel;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Shuchkin\SimpleXLSX;

class AdminTermsController extends Controller
{
    public function index()
    {
        return view('admin.terms.index', [
            'terms' => Term::with('yearLevels')->orderBy('academic_year', 'desc')->orderBy('start_date')->get(),
            'yearLevels' => YearLevel::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:semestral,quarteral',
            'year_level_ids' => 'required|array|min:1',
            'year_level_ids.*' => 'exists:year_levels,id',
            'academic_year' => 'required|integer|min:2000|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        $term = Term::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'academic_year' => $validated['academic_year'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'year_level_id' => null,
        ]);
        $term->yearLevels()->sync($validated['year_level_ids']);
        return response()->json(['success' => true, 'message' => 'Term saved']);
    }

    public function update(Request $request, $id)
    {
        $term = Term::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:semestral,quarteral',
            'year_level_ids' => 'required|array|min:1',
            'year_level_ids.*' => 'exists:year_levels,id',
            'academic_year' => 'required|integer|min:2000|max:2100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);
        $term->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'academic_year' => $validated['academic_year'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'year_level_id' => null,
        ]);
        $term->yearLevels()->sync($validated['year_level_ids']);
        return response()->json(['success' => true, 'message' => 'Term updated']);
    }

    public function destroy($id)
    {
        $term = Term::findOrFail($id);
        $term->delete();
        return response()->json(['success' => true, 'message' => 'Term deleted']);
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['success' => false, 'message' => 'No items selected.'], 400);
        }
        Term::whereIn('id', $ids)->delete();
        return response()->json(['success' => true, 'message' => count($ids) . ' terms deleted successfully.']);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Terms');
        $sheet->fromArray(['Name', 'Type', 'Academic Year', 'Start Date', 'End Date', 'Year Levels'], null, 'A1');
        $sheet->fromArray(['Semester 1', 'semestral', '2026', '2026-06-01', '2026-10-01', '1st Year,2nd Year,3rd Year,4th Year'], null, 'A2');
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        // Add Year Level Reference
        $ref = $spreadsheet->createSheet();
        $ref->setTitle('Year Level Reference');
        $ref->setCellValue('A1', 'Existing Year Levels');
        $levels = YearLevel::orderBy('name')->pluck('name')->toArray();
        $row = 2;
        foreach ($levels as $name) {
            $ref->setCellValue("A{$row}", $name);
            $row++;
        }
        $ref->getColumnDimension('A')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, 'terms_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|max:5120',
        ]);
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt', 'xlsx', 'xls'])) {
            return response()->json(['success' => false, 'message' => 'Invalid file format. Allowed: csv, txt, xlsx, xls'], 422);
        }

        // Load rows
        if ($ext === 'xlsx' || $ext === 'xls') {
            if ($xlsx = SimpleXLSX::parse($file->getPathname())) {
                $rows = $xlsx->rows();
            } else {
                return response()->json(['success' => false, 'message' => 'Unable to parse Excel: ' . SimpleXLSX::parseError()], 500);
            }
        } else {
            $rows = array_map('str_getcsv', file($file->getRealPath()));
        }

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'Empty file.'], 422);
        }

        $header = array_map(function($h){ return strtolower(trim($h ?? '')); }, array_shift($rows));
        // Preload year levels map
        $ylMap = YearLevel::orderBy('name')->get()->mapWithKeys(fn($yl) => [strtolower($yl->name) => $yl->id])->toArray();

        $count = 0;
        foreach ($rows as $row) {
            if (empty(array_filter($row))) continue;
            // helper
            $get = function($keys) use ($header, $row) {
                foreach ($keys as $k) {
                    $idx = array_search(strtolower($k), $header);
                    if ($idx !== false && isset($row[$idx])) return trim($row[$idx]);
                    foreach ($header as $i => $h) {
                        if (str_contains($h, strtolower($k))) return trim($row[$i] ?? '');
                    }
                }
                return null;
            };
            $name = $get(['name']);
            $type = strtolower($get(['type'])) ?: null;
            $ay = $get(['academic year','academic_year']);
            $start = $get(['start date','start_date']);
            $end = $get(['end date','end_date']);
            $yls = $get(['year levels','year_levels']);

            if (!$name || !$type || !$ay || !$start || !$end) continue;
            if (!in_array($type, ['semestral','quarteral'])) continue;

            // create term
            $term = Term::firstOrCreate([
                'name' => $name,
                'type' => $type,
                'academic_year' => (int) $ay,
                'start_date' => $start,
                'end_date' => $end,
            ], ['year_level_id' => null]);

            // map year levels
            $ids = [];
            if ($yls) {
                $parts = array_filter(array_map('trim', explode(',', $yls)));
                foreach ($parts as $p) {
                    $id = $ylMap[strtolower($p)] ?? null;
                    if ($id) $ids[] = $id;
                }
            }
            if (!empty($ids)) {
                $term->yearLevels()->syncWithoutDetaching($ids);
            }
            $count++;
        }
        return response()->json(['success' => true, 'message' => "Imported {$count} terms."]);
    }
}
