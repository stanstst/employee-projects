<?php

namespace Tests\Feature;

use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CommonProjectsTest extends TestCase
{
    public function test_it_returns_the_common_projects_of_the_sample_file(): void
    {
        $path = base_path('tests/data/sample.csv');

        $response = $this->post('/api/calculate-common-projects', [
            'file' => new UploadedFile($path, 'sample.csv', 'text/csv', null, true),
        ]);

        // 218 and 402 both work on project 10 with DateTo = NULL, i.e. until today.
        $daysUntilToday = (new DateTimeImmutable('2012-05-16'))->diff(new DateTimeImmutable('today'))->days + 1;

        $response->assertOk()->assertExactJson([
            'data' => [
                ['EmployeeID1' => 143, 'EmployeeID2' => 218, 'ProjectID' => 12, 'NumberOfDays' => 36],
                ['EmployeeID1' => 143, 'EmployeeID2' => 402, 'ProjectID' => 12, 'NumberOfDays' => 5],
                ['EmployeeID1' => 218, 'EmployeeID2' => 301, 'ProjectID' => 10, 'NumberOfDays' => 46],
                ['EmployeeID1' => 218, 'EmployeeID2' => 402, 'ProjectID' => 10, 'NumberOfDays' => $daysUntilToday],
                ['EmployeeID1' => 143, 'EmployeeID2' => 301, 'ProjectID' => 10, 'NumberOfDays' => 331],
                ['EmployeeID1' => 143, 'EmployeeID2' => 402, 'ProjectID' => 10, 'NumberOfDays' => 117],
                ['EmployeeID1' => 218, 'EmployeeID2' => 402, 'ProjectID' => 12, 'NumberOfDays' => 32],
                ['EmployeeID1' => 301, 'EmployeeID2' => 402, 'ProjectID' => 10, 'NumberOfDays' => 547],
                ['EmployeeID1' => 143, 'EmployeeID2' => 301, 'ProjectID' => 15, 'NumberOfDays' => 580],
                ['EmployeeID1' => 143, 'EmployeeID2' => 218, 'ProjectID' => 15, 'NumberOfDays' => 61],
                ['EmployeeID1' => 218, 'EmployeeID2' => 301, 'ProjectID' => 15, 'NumberOfDays' => 61],
            ],
        ]);
    }
}
