<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Csv\Reader;

class CalculateCommonProjectsController extends Controller
{
    /**
     * Read the uploaded CSV in place (it is never stored) and return its rows.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        // league/csv strips a UTF-8 BOM and skips empty lines on its own.
        $csv = Reader::from($request->file('file')->getRealPath());
        $csv->setEscape('');

        $rows = collect($csv->getRecords())
            ->values();

        return response()->json(['data' => $rows]);
    }
}
