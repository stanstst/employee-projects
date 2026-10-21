<?php

namespace Tests\Unit;

use App\Services\CommonWorkCalculator;
use App\Services\LongestPeriodWorkCalculator;
use Tests\TestCase;

class LongestPeriodWorkCalculatorTest extends TestCase
{
    public function test_it_passes_the_input_to_the_common_work_calculator(): void
    {
        $input = [['EmpID' => 1, 'ProjectID' => 10, 'DateFrom' => '2013-01-01', 'DateTo' => null]];

        $commonWorkCalculator = $this->createMock(CommonWorkCalculator::class);
        $commonWorkCalculator->expects($this->once())->method('calculate')->with($input)->willReturn([]);

        (new LongestPeriodWorkCalculator($commonWorkCalculator))->calculate($input);
    }

    public function test_each_project_gets_one_record_with_the_longest_pair_and_without_days(): void
    {
        $result = $this->calculateWith([
            $this->work(1, 2, 10, 5),
            $this->work(1, 3, 10, 20),
            $this->work(2, 3, 10, 7),
            $this->work(4, 5, 20, 3),
            $this->work(4, 6, 20, 9),
        ]);

        $this->assertSame([
            ['EmployeeID1' => 1, 'EmployeeID2' => 3, 'ProjectID' => 10],
            ['EmployeeID1' => 4, 'EmployeeID2' => 6, 'ProjectID' => 20],
        ], $result);
    }

    public function test_projects_keep_the_order_in_which_they_first_appear(): void
    {
        $result = $this->calculateWith([
            $this->work(1, 2, 20, 1),
            $this->work(3, 4, 10, 5),
            $this->work(5, 6, 20, 8),
        ]);

        $this->assertSame([
            ['EmployeeID1' => 5, 'EmployeeID2' => 6, 'ProjectID' => 20],
            ['EmployeeID1' => 3, 'EmployeeID2' => 4, 'ProjectID' => 10],
        ], $result);
    }

    public function test_on_equal_days_the_first_pair_is_kept(): void
    {
        $result = $this->calculateWith([
            $this->work(1, 2, 10, 10),
            $this->work(3, 4, 10, 10),
        ]);

        $this->assertSame([
            ['EmployeeID1' => 1, 'EmployeeID2' => 2, 'ProjectID' => 10],
        ], $result);
    }

    public function test_no_common_work_gives_an_empty_result(): void
    {
        $this->assertSame([], $this->calculateWith([]));
    }

    private function calculateWith(array $commonWork): array
    {
        $commonWorkCalculator = $this->createStub(CommonWorkCalculator::class);
        $commonWorkCalculator->method('calculate')->willReturn($commonWork);

        return (new LongestPeriodWorkCalculator($commonWorkCalculator))->calculate([]);
    }

    private function work(int $employee1, int $employee2, int $projectId, int $days): array
    {
        return ['EmployeeID1' => $employee1, 'EmployeeID2' => $employee2, 'ProjectID' => $projectId, 'NumberOfDays' => $days];
    }
}
