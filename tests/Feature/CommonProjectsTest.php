<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CommonProjectsTest extends TestCase
{
    private const URI = '/api/calculate-common-projects';

    public function test_it_returns_the_longest_working_pair_of_each_project_in_the_sample_file(): void
    {
        $path = base_path('tests/data/sample.csv');

        $response = $this->post(self::URI, [
            'file' => new UploadedFile($path, 'sample.csv', 'text/csv', null, true),
        ]);

        $response->assertOk()->assertExactJson([
            'data' => [
                ['EmployeeID1' => 143, 'EmployeeID2' => 218, 'ProjectID' => 12],
                ['EmployeeID1' => 218, 'EmployeeID2' => 402, 'ProjectID' => 10],
                ['EmployeeID1' => 143, 'EmployeeID2' => 301, 'ProjectID' => 15],
            ],
        ]);
    }

    public function test_each_project_gets_one_record_with_the_pair_that_worked_together_longest(): void
    {
        $response = $this->upload([
            '1,10,2013-01-01,2013-01-31',
            '2,10,2013-01-01,2013-01-10',
            '3,10,2013-01-01,2013-01-20',
            '4,20,2013-01-01,2013-01-05',
            '5,20,2013-01-01,2013-01-05',
        ]);

        // Project 10: 1/3 = 20 days beats 1/2 and 2/3 = 10 days each.
        $response->assertOk()->assertExactJson([
            'data' => [
                ['EmployeeID1' => 1, 'EmployeeID2' => 3, 'ProjectID' => 10],
                ['EmployeeID1' => 4, 'EmployeeID2' => 5, 'ProjectID' => 20],
            ],
        ]);
    }

    public function test_on_equal_days_in_a_project_the_first_pair_wins(): void
    {
        $response = $this->upload([
            '1,10,2013-01-01,2013-01-10',
            '2,10,2013-01-01,2013-01-10',
            '3,10,2013-02-01,2013-02-10',
            '4,10,2013-02-01,2013-02-10',
        ]);

        $response->assertOk()->assertExactJson([
            'data' => [
                ['EmployeeID1' => 1, 'EmployeeID2' => 2, 'ProjectID' => 10],
            ],
        ]);
    }

    public function test_no_common_projects_return_an_empty_list(): void
    {
        $response = $this->upload([
            '1,10,2013-01-01,2013-01-10',
            '2,20,2013-01-01,2013-01-10',
        ]);

        $response->assertOk()->assertExactJson(['data' => []]);
    }

    private function upload(array $rows)
    {
        $csv = "EmpID,ProjectID,DateFrom,DateTo\n" . implode("\n", $rows) . "\n";

        return $this->post(self::URI, [
            'file' => UploadedFile::fake()->createWithContent('projects.csv', $csv),
        ]);
    }
}
