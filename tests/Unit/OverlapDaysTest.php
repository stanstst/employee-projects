<?php

namespace Tests\Unit;

use App\Services\OverlapDays;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class OverlapDaysTest extends TestCase
{
    public function test_overlapping_periods_count_both_end_days(): void
    {
        $days = (new OverlapDays)->hasOverlap(
            $this->period('2013-11-01', '2014-01-05'),
            $this->period('2013-12-01', '2014-03-10'),
        );

        $this->assertSame(36, $days);
    }

    public function test_a_period_inside_another_counts_its_own_length(): void
    {
        $days = (new OverlapDays)->hasOverlap(
            $this->period('2013-01-01', '2013-12-31'),
            $this->period('2013-03-01', '2013-03-10'),
        );

        $this->assertSame(10, $days);
    }

    public function test_periods_sharing_a_single_day_overlap_by_one_day(): void
    {
        $days = (new OverlapDays)->hasOverlap(
            $this->period('2013-01-01', '2013-01-31'),
            $this->period('2013-01-31', '2013-02-28'),
        );

        $this->assertSame(1, $days);
    }

    public function test_separate_periods_do_not_overlap(): void
    {
        $days = (new OverlapDays)->hasOverlap(
            $this->period('2013-01-01', '2013-01-31'),
            $this->period('2013-02-01', '2013-02-28'),
        );

        $this->assertSame(0, $days);
    }

    public function test_the_order_of_the_periods_does_not_matter(): void
    {
        $a = $this->period('2013-11-01', '2014-01-05');
        $b = $this->period('2013-12-01', '2014-03-10');

        $this->assertSame((new OverlapDays)->hasOverlap($a, $b), (new OverlapDays)->hasOverlap($b, $a));
    }

    private function period(string $from, string $to): array
    {
        return ['from' => new DateTimeImmutable($from), 'to' => new DateTimeImmutable($to)];
    }
}
