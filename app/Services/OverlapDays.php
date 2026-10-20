<?php

namespace App\Services;

class OverlapDays
{
    public function hasOverlap(array $a, array $b): int
    {
        $from = max($a['from'], $b['from']);
        $to = min($a['to'], $b['to']);

        return $from <= $to ? $from->diff($to)->days + 1 : 0;
    }

}
