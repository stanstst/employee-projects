<?php

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;

class SameEmployeeOverlapValidator
{
    public function __construct(private OverlapDays $overlapDays)
    {
    }

    public function validate(array $projectsWork): void
    {
        $csvHeaders = config('projects.csvHeaders');

        $today = new DateTimeImmutable('today');

        $employeesPeriods = [];
        foreach ($projectsWork as $row) {
            $employeePeriod = [
                'employId' => $row[$csvHeaders['employeeId']],
                'projectId' => $row[$csvHeaders['projectId']],
                'from' => new DateTimeImmutable($row[$csvHeaders['dateFrom']]),
                'to' => $row[$csvHeaders['dateTo']] === null ? $today : new DateTimeImmutable($row[$csvHeaders['dateTo']]),
            ];

            if ($employeePeriod['from'] > $employeePeriod['to']) {
                throw new InvalidArgumentException(
                    "Employee {$row[$csvHeaders['employeeId']]} has a period on project {$row[$csvHeaders['projectId']]} that ends before it starts: "
                    . $this->formatPeriod($employeePeriod)
                );
            }

            $employeesPeriods[] = $employeePeriod;
        }

        foreach ($employeesPeriods as $i => $employeePeriodBase) {
            foreach (array_slice($employeesPeriods, $i + 1) as $emplPeriodSearch) {
                if ($employeePeriodBase['projectId'] !== $emplPeriodSearch['projectId'] || $employeePeriodBase['employId'] !== $emplPeriodSearch['employId']) {
                    continue;
                }

                if ($this->overlapDays->hasOverlap($employeePeriodBase, $emplPeriodSearch) > 0) {
                    throw new InvalidArgumentException(
                        "Employee {$employeePeriodBase['employId']} has overlapping periods on project {$employeePeriodBase['projectId']}: "
                        . $this->formatPeriod($employeePeriodBase) . ' and ' . $this->formatPeriod($emplPeriodSearch)
                    );
                }
            }
        }
    }

    private function formatPeriod(array $period): string
    {
        return $period['from']->format('Y-m-d') . ' - ' . $period['to']->format('Y-m-d');
    }

}
