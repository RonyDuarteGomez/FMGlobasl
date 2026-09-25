<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(preg_match('~^/(sistema(?:/.*)?|ingresar)$~',$path)){require dirname(__DIR__,2).'/public/clean.php';return true;}
return false;
