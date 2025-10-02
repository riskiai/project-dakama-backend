<?php

namespace App\Facades;

use App\Models\Attendance;
use App\Models\Overtime;
use Carbon\Carbon;

class AttendanceOvertimeService
{
    /**
     * Get complete attendance and overtime records with filled missing dates
     */
    public function getCompleteRecords($userId, array $dateTime)
    {
        $attendanceRecords = $this->getAttendanceRecords($userId, $dateTime);
        $overtimeRecords = $this->getOvertimeRecords($userId, $dateTime);

        return $this->mergeAndFillRecords($attendanceRecords, $overtimeRecords, $dateTime);
    }

    /**
     * Get attendance records from database
     */
    private function getAttendanceRecords($userId, array $dateTime)
    {
        return Attendance::with(['project.company.contactType', 'budget', 'overtime'])
            ->where('type', 0)
            ->where('user_id', $userId)
            ->whereBetween('start_time', $dateTime)
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->start_time)->format('Y-m-d');
            });
    }

    /**
     * Get overtime records from database
     */
    private function getOvertimeRecords($userId, array $dateTime)
    {
        return Attendance::with(['project.company.contactType', 'budget', 'overtime'])
            ->where('type', 1)
            ->where('user_id', $userId)
            ->whereBetween('start_time', $dateTime)
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->start_time)->format('Y-m-d');
            });
    }

    /**
     * Merge attendance and overtime, fill missing dates
     */
    private function mergeAndFillRecords($attendanceRecords, $overtimeRecords, array $dateTime)
    {
        $result = [];
        $currentDate = Carbon::parse($dateTime[0]);
        $endDateCarbon = Carbon::parse($dateTime[1]);

        while ($currentDate->lte($endDateCarbon)) {
            $dateStr = $currentDate->format('Y-m-d');

            $attendance = $attendanceRecords->has($dateStr)
                ? $this->formatAttendance($attendanceRecords->get($dateStr))
                : $this->getEmptyAttendance();

            $overtime = $overtimeRecords->has($dateStr)
                ? $this->formatOvertime($overtimeRecords->get($dateStr))
                : $this->getEmptyOvertime();

            $result[] = [
                'date' => $currentDate->format('d'),
                'date_full' => $dateStr,
                'day_name' => $currentDate->locale('id')->isoFormat('dddd'),
                'day_name_short' => $currentDate->locale('id')->isoFormat('ddd'),
                'day_name_single' => $currentDate->locale('id')->isoFormat('dd')[0],
                'is_weekend' => $currentDate->dayOfWeek() === Carbon::SUNDAY,
                'attendance' => $attendance,
                'overtime' => $overtime,
            ];

            $currentDate->addDay();
        }

        return $result;
    }

    /**
     * Format attendance record
     */
    private function formatAttendance($record)
    {
        return $record;
    }

    /**
     * Format overtime record
     */
    private function formatOvertime($record)
    {
        return $record;
    }

    /**
     * Get empty attendance template
     */
    private function getEmptyAttendance()
    {
        return [];
    }

    /**
     * Get empty overtime template
     */
    private function getEmptyOvertime()
    {
        return [];
    }

    /**
     * Get summary statistics
     */
    public function getSummary($userId, array $dateTime)
    {
        $records = $this->getCompleteRecords($userId, $dateTime);

        $totalDays = count($records);
        $presentDays = collect($records)->where('attendance.is_present', true)->count();
        $absentDays = $totalDays - $presentDays;
        $overtimeDays = collect($records)->where('overtime.has_overtime', true)->count();
        $totalOvertimeHours = collect($records)->sum('overtime.duration_hours');

        return [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'overtime_days' => $overtimeDays,
            'total_overtime_hours' => round($totalOvertimeHours, 2),
        ];
    }
}
