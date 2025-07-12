<?php

namespace App\Facades;

class Helper
{
    public static function calculateLateCut($dailySalary, $lateMinute)
    {
        $cutPerMinute = ($dailySalary / 8) / 60;

        $cutTotal = $cutPerMinute * $lateMinute;

        return (int) ceil($cutTotal);
    }

    public static function calculateDurationTime($start, $end)
    {
        return (strtotime($end) - strtotime($start)) / 60 / 60;
    }
}
