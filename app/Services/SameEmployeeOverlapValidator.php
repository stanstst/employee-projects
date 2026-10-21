<?php

namespace App\Services;

use App\Utils\DateTimeFactory;
use DateTimeImmutable;
use InvalidArgumentException;

class SameEmployeeOverlapValidator
{
    public function __construct(private readonly OverlapDays $overlapDays, private readonly DateTimeFactory $dateTimeFactory)
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
                'from' => $this->parseDate($row, $csvHeaders['dateFrom']),
                'to' => $row[$csvHeaders['dateTo']] === null ? $today : $this->parseDate($row, $csvHeaders['dateTo']),
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

    private function parseDate(array $row, string $column): DateTimeImmutable
    {
        $csvHeaders = config('projects.csvHeaders');
        $value = (string) $row[$column];

        try {
            return $this->dateTimeFactory->create($value);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException(
                "Employee {$row[$csvHeaders['employeeId']]} has an invalid {$column} on project {$row[$csvHeaders['projectId']]}: \"{$value}\". "
                . 'Use a valid date such as 2013-11-01.'
            );
        }
    }

    private function formatPeriod(array $period): string
    {
        return $period['from']->format('Y-m-d') . ' - ' . $period['to']->format('Y-m-d');
    }

}
