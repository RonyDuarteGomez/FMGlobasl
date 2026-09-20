<?php
namespace FMGlobal\Support;
final class Logger
{
    public static function exception(\Throwable $error, string $operation): string
    {
        $id = bin2hex(random_bytes(8));
        // No incluir mensajes, argumentos, URLs, cuerpos de correo ni tokens: pueden contener secretos.
        $record = ['time'=>date(DATE_ATOM),'id'=>$id,'operation'=>$operation,'type'=>get_class($error),'code'=>$error->getCode(),'file'=>basename($error->getFile()),'line'=>$error->getLine()];
        $dir = FM_ROOT.'/storage/logs';
        if (!is_dir($dir)) @mkdir($dir, 0700, true);
        $line = json_encode($record, JSON_UNESCAPED_SLASHES).PHP_EOL;
        if (@file_put_contents($dir.'/application.log', $line, FILE_APPEND | LOCK_EX) === false) error_log($line);
        return $id;
    }
}
