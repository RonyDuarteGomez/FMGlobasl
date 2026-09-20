<?php
namespace FMGlobal\Security;
final class ActivityScope
{
    public static function operations(array $permissions): array
    {
        $ops=[];
        if (!empty($permissions['reports.public'])) $ops=[1,2];
        if (!empty($permissions['services.advisor'])) $ops=array_merge($ops,[3,4,5]);
        if (!empty($permissions['services.support']) || !empty($permissions['reports.support'])) $ops[]=6;
        return $ops;
    }
    public static function user(array $permissions,string $username): ?string
    {
        return !empty($permissions['activity.all'])?null:$username;
    }
    public static function visible(array $permissions): bool
    {
        return !empty($permissions['activity.all']) || !empty($permissions['activity.own']);
    }
}
