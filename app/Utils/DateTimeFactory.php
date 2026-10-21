<?php

namespace App\Utils;

use DateTimeImmutable;
use InvalidArgumentException;

class DateTimeFactory
{
    private string $dateFormat;

    /**
     * @param string $dateFormatKey a key of config('projects.dateFormats'); unknown keys use 'default'
     */
    public function __construct(string $dateFormatKey = 'default')
    {
        $dateFormats = config('projects.dateFormats');

        $this->dateFormat = $dateFormats[$dateFormatKey] ?? $dateFormats['default'];
    }

    public function create(string $dateString): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!' . $this->dateFormat, $dateString);

        $errors = DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && $errors['warning_count'] > 0)) {
            throw new InvalidArgumentException("\"{$dateString}\" is not a valid date");
        }

        return $date;
    }

    public function example(): string
    {
        return (new DateTimeImmutable('2013-11-01'))->format($this->dateFormat);
    }
}
