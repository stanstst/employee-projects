<?php

namespace App\Services;

use DateTimeImmutable;

class CommonWorkCalculator
{

    public function calculate(array $input): array
    {
        $csvHeaders = config('projects.csvHeaders');

        $combined = [];

        $today = new DateTimeImmutable('today');

        $employeesPeriods = [];
        foreach ($input as $row) {
            $employeesPeriods[] = [
                $csvHeaders['employeeId'] => $row[$csvHeaders['employeeId']],
                $csvHeaders['projectId'] => $row[$csvHeaders['projectId']],
                'from' => new DateTimeImmutable($row[$csvHeaders['dateFrom']]),
                'to' => $row[$csvHeaders['dateTo']] === null ? $today : new DateTimeImmutable($row[$csvHeaders['dateTo']]),
            ];
        }

        foreach ($employeesPeriods as $i => $employeePeriodBase) {
            foreach (array_slice($employeesPeriods, $i + 1) as $emplPeriodSearch) {
                if ($employeePeriodBase[$csvHeaders['projectId']] !== $emplPeriodSearch[$csvHeaders['projectId']] || $employeePeriodBase[$csvHeaders['employeeId']] === $emplPeriodSearch[$csvHeaders['employeeId']]) {
                    continue;
                }

                $overlapDays = $this->overlapDays($employeePeriodBase, $emplPeriodSearch);
                if ($overlapDays === 0) {
                    continue;
                }

                $employee1 = min($employeePeriodBase[$csvHeaders['employeeId']], $emplPeriodSearch[$csvHeaders['employeeId']]);
                $employee2 = max($employeePeriodBase[$csvHeaders['employeeId']], $emplPeriodSearch[$csvHeaders['employeeId']]);
                $key = "{$employeePeriodBase[$csvHeaders['projectId']]}-{$employee1}-{$employee2}";

                $combined[$key] ??= [
                    'EmployeeID1' => $employee1,
                    'EmployeeID2' => $employee2,
                    $csvHeaders['projectId'] => $employeePeriodBase[$csvHeaders['projectId']],
                    'NumberOfDays' => 0,
                ];
                $combined[$key]['NumberOfDays'] += $overlapDays;
            }
        }

        return array_values($combined);
    }

    private function overlapDays(array $a, array $b): int
    {
        $from = max($a['from'], $b['from']);
        $to = min($a['to'], $b['to']);

        return $from <= $to ? $from->diff($to)->days + 1 : 0;
    }
}
