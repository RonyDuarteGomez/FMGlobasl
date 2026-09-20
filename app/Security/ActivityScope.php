<?php
namespace FMGlobal\Security;
final class ActivityScope
{
    public static function operations(array $permissions): array
    {
        $ops=[];
        if ((!empty($permissions['reports.public']) || !empty($permissions['services.activation']))) $ops=[1,2];
        if ((!empty($permissions['services.advisor']) || !empty($permissions['reports.internal']))) $ops=array_merge($ops,[3,4,5]);
        if (!empty($permissions['services.support']) || !empty($permissions['reports.support'])) $ops[]=6;
        return $ops;
    }
    public static function user(array $permissions,int $userId,bool $administrator=false): ?int
    {
        return $administrator?null:$userId;
    }
    public static function visible(array $permissions): bool
    {
        foreach ($permissions as $code=>$allowed) {
            if ($allowed && (str_starts_with($code,'services.') || str_starts_with($code,'reports.'))) return true;
        }
        return false;
    }
}
