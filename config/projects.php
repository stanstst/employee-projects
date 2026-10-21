<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CSV Headers
    |--------------------------------------------------------------------------
    |
    | Maps each field to its column name in the uploaded CSV's header row.
    |
    */

    'csvHeaders' => [
        'employeeId' => 'EmpID',
        'projectId' => 'ProjectID',
        'dateFrom' => 'DateFrom',
        'dateTo' => 'DateTo',
    ],
    'dateFormats' => ['default' => 'Y-m-d', 'euro' => 'd.m.Y']
];
