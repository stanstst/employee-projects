<?php

namespace Tests\Unit;

use App\Services\CommonWorkCalculator;
use App\Services\OverlapDays;
use App\Utils\DateTimeFactory;
use DateTimeImmutable;
use Tests\TestCase;

class CommonWorkCalculatorTest extends TestCase
{
    public function test_it_returns_the_overlap_of_two_employees_on_the_same_project(): void
    {
        $this->bindDateTimeFactory();

        $overlapDays = $this->createMock(OverlapDays::class);
        $overlapDays->expects($this->once())->method('hasOverlap')->willReturn(7);

        $result = (new CommonWorkCalculator($overlapDays))->calculate([
            $this->row(218, 10, '2013-01-01', '2013-12-31'),
            $this->row(143, 10, '2013-06-01', '2013-06-30'),
        ]);

        $this->assertSame([
            ['EmployeeID1' => 143, 'EmployeeID2' => 218, 'ProjectID' => 10, 'NumberOfDays' => 7],
        ], $result);
    }

    public function test_it_adds_up_the_days_of_several_periods_of_the_same_pair_and_project(): void
    {
        $this->bindDateTimeFactory();

        $overlapDays = $this->createMock(OverlapDays::class);
        $overlapDays->expects($this->exactly(2))->method('hasOverlap')->willReturnOnConsecutiveCalls(3, 4);

        $result = (new CommonWorkCalculator($overlapDays))->calculate([
            $this->row(1, 10, '2013-01-01', '2013-01-31'),
            $this->row(1, 10, '2013-03-01', '2013-03-31'),
            $this->row(2, 10, '2013-01-01', '2013-12-31'),
        ]);

        $this->assertSame([
            ['EmployeeID1' => 1, 'EmployeeID2' => 2, 'ProjectID' => 10, 'NumberOfDays' => 7],
        ], $result);
    }

    public function test_pairs_without_overlapping_days_are_left_out(): void
    {
        $this->bindDateTimeFactory();

        $overlapDays = $this->createMock(OverlapDays::class);
        $overlapDays->expects($this->once())->method('hasOverlap')->willReturn(0);

        $result = (new CommonWorkCalculator($overlapDays))->calculate([
            $this->row(1, 10, '2013-01-01', '2013-01-31'),
            $this->row(2, 10, '2014-01-01', '2014-01-31'),
        ]);

        $this->assertSame([], $result);
    }

    public function test_different_projects_and_the_same_employee_are_never_compared(): void
    {
        $this->bindDateTimeFactory();

        $overlapDays = $this->createMock(OverlapDays::class);
        $overlapDays->expects($this->never())->method('hasOverlap');

        $result = (new CommonWorkCalculator($overlapDays))->calculate([
            $this->row(1, 10, '2013-01-01', '2013-12-31'),
            $this->row(1, 10, '2014-01-01', '2014-12-31'),
            $this->row(2, 11, '2013-01-01', '2013-12-31'),
        ]);

        $this->assertSame([], $result);
    }

    public function test_an_empty_date_to_means_until_today(): void
    {
        $dateTimeFactory = $this->createMock(DateTimeFactory::class);
        $dateTimeFactory->expects($this->exactly(3))
            ->method('create')
            ->willReturnCallback(fn (string $date) => new DateTimeImmutable($date));
        $this->app->instance(DateTimeFactory::class, $dateTimeFactory);

        $overlapDays = $this->createMock(OverlapDays::class);
        $overlapDays->expects($this->once())
            ->method('hasOverlap')
            ->with(
                $this->callback(fn (array $period) => $period['to'] == new DateTimeImmutable('2013-12-31')),
                $this->callback(fn (array $period) => $period['to'] == new DateTimeImmutable('today')),
            )
            ->willReturn(1);

        (new CommonWorkCalculator($overlapDays))->calculate([
            $this->row(1, 10, '2013-01-01', '2013-12-31'),
            $this->row(2, 10, '2013-06-01', null),
        ]);
    }

    private function bindDateTimeFactory(): void
    {
        $dateTimeFactory = $this->createStub(DateTimeFactory::class);
        $dateTimeFactory->method('create')->willReturnCallback(fn (string $date) => new DateTimeImmutable($date));

        $this->app->instance(DateTimeFactory::class, $dateTimeFactory);
    }

    private function row(int $employeeId, int $projectId, string $dateFrom, ?string $dateTo): array
    {
        return ['EmpID' => $employeeId, 'ProjectID' => $projectId, 'DateFrom' => $dateFrom, 'DateTo' => $dateTo];
    }
}
