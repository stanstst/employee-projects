<?php

namespace App\Services;

use DateTimeImmutable;

class CommonWorkCalculator
{

    public function calculate(array $input): array
    {
        $combined = [];

        $today = new DateTimeImmutable('today');

        $employeesPeriods = [];
        foreach ($input as $row) {
            $employeesPeriods[] = [
                'EmpID' => $row['EmpID'],
                'ProjectID' => $row['ProjectID'],
                'from' => new DateTimeImmutable($row['DateFrom']),
                'to' => $row['DateTo'] === null ? $today : new DateTimeImmutable($row['DateTo']),
            ];
        }

        foreach ($employeesPeriods as $i => $employeePeriodBase) {
            foreach (array_slice($employeesPeriods, $i + 1) as $emplPeriodSearch) {
                if ($employeePeriodBase['ProjectID'] !== $emplPeriodSearch['ProjectID'] || $employeePeriodBase['EmpID'] === $emplPeriodSearch['EmpID']) {
                    continue;
                }

                $overlapDays = $this->overlapDays($employeePeriodBase, $emplPeriodSearch);
                if ($overlapDays === 0) {
                    continue;
                }

                $employee1 = min($employeePeriodBase['EmpID'], $emplPeriodSearch['EmpID']);
                $employee2 = max($employeePeriodBase['EmpID'], $emplPeriodSearch['EmpID']);
                $key = "{$employeePeriodBase['ProjectID']}-{$employee1}-{$employee2}";

                $combined[$key] ??= [
                    'EmpID1' => $employee1,
                    'EmpID2' => $employee2,
                    'ProjectID' => $employeePeriodBase['ProjectID'],
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
