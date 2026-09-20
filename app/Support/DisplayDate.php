<?php
namespace FMGlobal\Support;
final class DisplayDate
{
    public static function dateTime(?string $value): string
    {
        if (!$value || str_starts_with($value,'0000-00-00')) return '—';
        try { return (new \DateTimeImmutable($value))->format('d/m/Y h:i A'); }
        catch (\Exception $e) { return '—'; }
    }
}