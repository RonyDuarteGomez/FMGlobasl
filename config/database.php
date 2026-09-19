<?php
// Por defecto se conservan los valores de produccion importados.
$private = require __DIR__ . '/private.php';
$env = static function (string $name, $default) { $value = getenv($name); return $value === false ? $default : $value; };
return [
    'host' => $env('FMGLOBAL_DB_HOST', "localhost"),
    'user' => $env('FMGLOBAL_DB_USER', "fmglobal_fmglobal"),
    'password' => $env('FMGLOBAL_DB_PASSWORD', $private['db_password']),
    'database' => $env('FMGLOBAL_DB_NAME', "fmglobal_streaming"),
    'charset' => 'utf8',
];
