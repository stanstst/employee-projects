<?php

namespace App\Http\Controllers;

use App\Services\CommonWorkCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;

class CalculateCommonProjectsController extends Controller
{

    public function __invoke(Request $request, CommonWorkCalculator $calculator): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $csv = Reader::from($request->file('file')->getRealPath());
        $csv->setEscape('');
        $csv->setHeaderOffset(0);

        $header = array_map('trim', $csv->getHeader());
        $columns = ['EmpID', 'ProjectID', 'DateFrom', 'DateTo'];

        if (array_diff($columns, $header) !== []) {
            throw ValidationException::withMessages([
                'file' => 'The file must have the columns: ' . implode(', ', $columns) . '.',
            ]);
        }

        $rows = collect($csv->getRecords($header))
            ->map(fn(array $row) => [
                'EmpID' => (int)$row['EmpID'],
                'ProjectID' => (int)$row['ProjectID'],
                'DateFrom' => trim($row['DateFrom']),
                'DateTo' => in_array(strtoupper(trim((string)$row['DateTo'])), ['', 'NULL'], true) ? null : trim($row['DateTo']),
            ])
            ->values()
            ->all();

        $result = $calculator->calculate($rows);

        return response()->json(['data' => $result]);
    }
}
