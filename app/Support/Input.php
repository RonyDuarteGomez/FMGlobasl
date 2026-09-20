<?php
namespace FMGlobal\Support;
use FMGlobal\Http\HttpException;
final class Input
{
    public static function text(array $input, string $key, int $max = 255, bool $required = false, bool $trim = true): string
    {
        $value = $input[$key] ?? '';
        if (!is_string($value)) throw new HttpException(422, 'Formato no válido: '.$key);
        if ($trim) $value = trim($value);
        if (($required && $value === '') || mb_strlen($value, 'UTF-8') > $max) throw new HttpException(422, 'Revisa el campo: '.$key);
        return $value;
    }
    public static function id(array $input, string $key): int
    {
        $value = $input[$key] ?? null;
        if (!is_scalar($value) || filter_var($value, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]) === false) throw new HttpException(422, 'Identificador no válido: '.$key);
        return (int)$value;
    }
    public static function email(array $input, string $key = 'correo', bool $required = true): string
    {
        $value = self::text($input, $key, 254, $required);
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) throw new HttpException(422, 'Correo no válido.');
        return $value;
    }
}
