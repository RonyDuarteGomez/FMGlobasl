<?php
namespace FMGlobal\Services\Reports;

use FMGlobal\Repositories\UsageRepository;

final class DashboardData
{
    public function __construct(private UsageRepository $usage) {}

    public function build(): array
    {
        $public = self::series($this->usage->totalsByOperation([1,2]), [1,2]);
        $internal = self::series($this->usage->totalsByOperation([3,4,5,6]), [3,4,5,6]);
        return [
            'labels' => $public['labels'], 'valores1_clean' => $public['values'][1], 'valores2_clean' => $public['values'][2],
            'labels_4l' => $internal['labels'], 'valores1_clean_4l' => $internal['values'][3],
            'valores2_clean_4l' => $internal['values'][4], 'valores3_clean_4l' => $internal['values'][5], 'valores4_clean_4l' => $internal['values'][6],
        ];
    }

    public static function series(array $rows, array $operations): array
    {
        $days = []; $values = array_fill_keys($operations, []); $counts = [];
        foreach ($rows as $row) {
            $days[$row['fecha']] = true;
            $counts[(int)$row['streaming']][$row['fecha']] = (int)$row['total_consultas'];
        }
        foreach (array_keys($days) as $day) {
            foreach ($operations as $operation) $values[$operation][] = $counts[$operation][$day] ?? 0;
        }
        return ['labels' => array_keys($days), 'values' => $values];
    }
}
