
<?php

ini_set('display_errors', 1);

error_reporting(E_ALL);

$cmd =
    '/home/fmglobal/exe/Token.exe';

$output =
    shell_exec($cmd . ' 2>&1');

echo "<pre>";

print_r($output);

echo "</pre>";

