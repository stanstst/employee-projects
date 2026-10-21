<?php

namespace Tests\Unit;

use App\Services\OverlapDays;
use App\Services\SameEmployeeOverlapValidator;
use App\Utils\DateTimeFactory;
use DateTimeImmutable;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class SameEmployeeOverlapValidatorTest extends TestCase
{
    public function test_valid_periods_pass_and_only_the_same_employee_and_project_are_compared(): void
    {
        $overlapDays = $this->createMock(OverlapDays::class);
        $overlapDays->expects($this->once())->method('hasOverlap')->willReturn(0);

        $validator = new SameEmployeeOverlapValidator($overlapDays, $this->dateTimeFactory());

        $validator->validate([
            $this->row(1, 10, '2013-01-01', '2013-01-31'),
            $this->row(1, 10, '2013-02-01', '2013-02-28'),
            $this->row(2, 10, '2013-01-01', '2013-12-31'),
            $this->row(1, 11, '2013-01-01', '2013-12-31'),
        ]);
    }

    public function test_a_period_that_ends_before_it_starts_is_rejected(): void
    {
        $overlapDays = $this->createMock(OverlapDays::class);
        $overlapDays->expects($this->never())->method('hasOverlap');

        $validator = new SameEmployeeOverlapValidator($overlapDays, $this->dateTimeFactory());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Employee 143 has a period on project 12 that ends before it starts: 2014-01-05 - 2013-11-01');

        $validator->validate([$this->row(143, 12, '2014-01-05', '2013-11-01')]);
    }

    public function test_overlapping_periods_of_the_same_employee_and_project_are_rejected(): void
    {
        $overlapDays = $this->createMock(OverlapDays::class);
        $overlapDays->expects($this->once())->method('hasOverlap')->willReturn(5);

        $validator = new SameEmployeeOverlapValidator($overlapDays, $this->dateTimeFactory());

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Employee 143 has overlapping periods on project 12: 2013-01-01 - 2013-06-01 and 2013-05-01 - 2013-09-01');

        $validator->validate([
            $this->row(143, 12, '2013-01-01', '2013-06-01'),
            $this->row(143, 12, '2013-05-01', '2013-09-01'),
        ]);
    }

    public function test_an_invalid_date_is_rejected_with_an_example_in_the_active_format(): void
    {
        $overlapDays = $this->createMock(OverlapDays::class);
        $overlapDays->expects($this->never())->method('hasOverlap');

        $dateTimeFactory = $this->createMock(DateTimeFactory::class);
        $dateTimeFactory->expects($this->once())
            ->method('create')
            ->with('2013-11-01')
            ->willThrowException(new InvalidArgumentException('"2013-11-01" is not a valid date'));
        $dateTimeFactory->expects($this->once())->method('example')->willReturn('01.11.2013');

        $validator = new SameEmployeeOverlapValidator($overlapDays, $dateTimeFactory);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Employee 143 has an invalid DateFrom on project 12: "2013-11-01". Use a valid date such as 01.11.2013.');

        $validator->validate([$this->row(143, 12, '2013-11-01', null)]);
    }

    public function test_an_empty_date_to_is_not_parsed(): void
    {
        $overlapDays = $this->createStub(OverlapDays::class);

        $dateTimeFactory = $this->createMock(DateTimeFactory::class);
        $dateTimeFactory->expects($this->once())
            ->method('create')
            ->with('2013-11-01')
            ->willReturn(new DateTimeImmutable('2013-11-01'));

        $validator = new SameEmployeeOverlapValidator($overlapDays, $dateTimeFactory);

        $validator->validate([$this->row(143, 12, '2013-11-01', null)]);
    }

    private function dateTimeFactory(): DateTimeFactory
    {
        $dateTimeFactory = $this->createStub(DateTimeFactory::class);
        $dateTimeFactory->method('create')->willReturnCallback(fn (string $date) => new DateTimeImmutable($date));

        return $dateTimeFactory;
    }

    private function row(int $employeeId, int $projectId, string $dateFrom, ?string $dateTo): array
    {
        return ['EmpID' => $employeeId, 'ProjectID' => $projectId, 'DateFrom' => $dateFrom, 'DateTo' => $dateTo];
    }
}
