<?php
$schedule=(new \FMGlobal\Repositories\PublicScheduleRepository(database()))->load();
$active=\FMGlobal\Services\Schedules\WeeklySchedule::active($schedule['week']);
require FM_ROOT.'/resources/views/Schedules/index.php';