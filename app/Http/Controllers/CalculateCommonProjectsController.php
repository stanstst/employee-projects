<?php

namespace App\Http\Controllers;

use App\Services\CommonWorkCalculator;
use App\Services\SameEmployeeOverlapValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use League\Csv\Reader;

class CalculateCommonProjectsController extends Controller
{

    public function __invoke(Request $request, CommonWorkCalculator $calculator, SameEmployeeOverlapValidator $sameEmployeeOverlapValidator): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $csv = Reader::from($request->file('file')->getRealPath());
        $csv->setEscape('');
        $csv->setHeaderOffset(0);

        $header = array_map('trim', $csv->getHeader());
        $headers = config('projects.csvHeaders');
        $columns = array_values($headers);

        if (array_diff($columns, $header) !== []) {
            throw ValidationException::withMessages([
                'file' => 'The file must have the columns: ' . implode(', ', $columns) . '.',
            ]);
        }

        $rows = collect($csv->getRecords($header))
            ->map(fn(array $row) => [
                $headers['employeeId'] => (int)$row[$headers['employeeId']],
                $headers['projectId'] => (int)$row[$headers['projectId']],
                $headers['dateFrom'] => trim($row[$headers['dateFrom']]),
                $headers['dateTo'] => in_array(strtoupper(trim((string)$row[$headers['dateTo']])), ['', 'NULL'], true) ? null : trim($row[$headers['dateTo']]),
            ])
            ->values()
            ->all();

        $sameEmployeeOverlapValidator->validate($rows);

        $result = $calculator->calculate($rows);

        return response()->json(['data' => $result]);
    }
}
