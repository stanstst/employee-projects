<?php

namespace App\Utils;

use DateTimeImmutable;
use InvalidArgumentException;

class DateTimeFactory
{
    public function create(string $dateString): DateTimeImmutable
    {
        foreach (config('projects.dateFormats') as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $dateString);

            $errors = DateTimeImmutable::getLastErrors();

            if ($date !== false && ($errors === false || $errors['warning_count'] === 0)) {
                return $date;
            }
        }

        throw new InvalidArgumentException("\"{$dateString}\" is not a valid date");
    }

    public function example(): string
    {
        return (new DateTimeImmutable('2013-11-01'))->format(config('projects.dateFormats')[0]);
    }
}
