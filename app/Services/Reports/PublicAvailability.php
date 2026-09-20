<?php
namespace FMGlobal\Services\Reports;
use FMGlobal\Services\Schedules\WeeklySchedule;
final class PublicAvailability
{
    /** Compatibility adapter for callers that still provide legacy blocking rows. */
    public static function current(array $rows, ?\DateTimeImmutable $now=null): ?bool
    {
        $now=($now??new \DateTimeImmutable('now'))->setTimezone(new \DateTimeZone('America/Lima'));
        $day=\FMGlobal\Services\Users\ScheduleService::DAYS[(int)$now->format('N')];
        foreach($rows as $row)if(strtr(mb_strtoupper(trim($row['dia'])),['É'=>'E','Á'=>'A'])===$day)return WeeklySchedule::active(WeeklySchedule::legacy($rows),$now);
        return null;
    }
}