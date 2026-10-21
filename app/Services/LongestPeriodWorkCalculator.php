<?php

namespace App\Services;

readonly class LongestPeriodWorkCalculator
{
    public function __construct(private CommonWorkCalculator $commonWorkCalculator)
    {
    }

    public function calculate(array $input): array
    {
        $commonWork = $this->commonWorkCalculator->calculate($input);
        $projectId = config('projects.csvHeaders')['projectId'];

        $longestPerProject = [];
        foreach ($commonWork as $work) {
            $current = $longestPerProject[$work[$projectId]] ?? null;

            if ($current === null || $work['NumberOfDays'] > $current['NumberOfDays']) {
                $longestPerProject[$work[$projectId]] = $work;
            }
        }

        return array_values(array_map(fn (array $work) => [
            'EmployeeID1' => $work['EmployeeID1'],
            'EmployeeID2' => $work['EmployeeID2'],
            $projectId => $work[$projectId],
        ], $longestPerProject));
    }

}
