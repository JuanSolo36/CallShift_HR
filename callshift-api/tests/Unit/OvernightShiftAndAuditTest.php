<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ShiftType;
use App\Models\ScheduleAssignment;
use App\Models\AuditLog;
use App\Enums\DayType;
use BadMethodCallException;

class OvernightShiftAndAuditTest extends TestCase
{
    public function test_shift_type_crosses_midnight_detection(): void
    {
        $overnightShift = new ShiftType([
            'name'             => 'Nocturno 22-06',
            'code'             => 'N22_06',
            'start_time'       => '22:00:00',
            'end_time'         => '06:00:00',
            'crosses_midnight' => true,
            'total_work_hours' => 7.0,
        ]);

        $this->assertTrue($overnightShift->crosses_midnight);
        $this->assertEquals(7.0, $overnightShift->total_work_hours);
    }

    public function test_schedule_assignment_date_interval_integrity(): void
    {
        $assignment = new ScheduleAssignment([
            'date'        => '2026-08-24',
            'starts_at'   => '2026-08-24 22:00:00',
            'ends_at'     => '2026-08-25 06:00:00',
            'day_type'    => DayType::WORK,
            'total_hours' => 7.0,
        ]);

        $this->assertEquals(DayType::WORK, $assignment->day_type);
        $this->assertTrue($assignment->day_type->isWorking());
        $this->assertEquals('2026-08-24', $assignment->date->format('Y-m-d'));
        $this->assertEquals('2026-08-24 22:00:00', $assignment->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-08-25 06:00:00', $assignment->ends_at->format('Y-m-d H:i:s'));
    }
}
