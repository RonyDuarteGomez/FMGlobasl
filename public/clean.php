<?php
require dirname(__DIR__).'/bootstrap/app.php';
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if (preg_match('~/sistema(?:/(.*))?$~',$path,$match)) {
    $slug=rtrim($match[1]??'','/');
    if ($slug!=='' && !in_array($slug,\FMGlobal\Support\CleanUrls::MODULES,true)) {
        http_response_code(404); echo 'Página no encontrada.'; exit;
    }
    fm_dispatch('home.php');
} elseif (str_ends_with($path,'/ingresar')) {
    fm_dispatch('login.php');
} else {
    http_response_code(404); echo 'Página no encontrada.';
}