<?php
// Por defecto se conservan los valores de produccion importados.
$private = require __DIR__ . '/private.php';
$local = is_file(__DIR__ . '/database.local.php') ? require __DIR__ . '/database.local.php' : [];
if (!is_array($local)) throw new RuntimeException('La configuracion local debe devolver un array.');
$env = static function (string $name, $default) { $value = getenv($name); return $value === false ? $default : $value; };
return [
    'host' => $env('FMGLOBAL_DB_HOST', $local['host'] ?? "localhost"),
    'user' => $env('FMGLOBAL_DB_USER', $local['user'] ?? "fmglobal_fmglobal"),
    'password' => $env('FMGLOBAL_DB_PASSWORD', $local['password'] ?? $private['db_password']),
    'database' => $env('FMGLOBAL_DB_NAME', $local['database'] ?? "fmglobal_streaming"),
    'charset' => 'utf8',
];
