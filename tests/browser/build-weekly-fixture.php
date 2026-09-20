<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__,2).'/bootstrap/app.php';
\FMGlobal\Security\Session::start();
$schedule=['revision'=>0,'ready'=>true,'week'=>array_fill(1,7,['mode'=>'all','slots'=>[]])];$active=true;
ob_start();require FM_ROOT.'/resources/views/Schedules/index.php';$view=ob_get_clean();
$base='file:///'.str_replace('\\','/',FM_ROOT).'/public/';
$html='<!doctype html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><base href="'.$base.'"><link rel="stylesheet" href="assets/css/main.css"><title>Weekly UI test</title></head><body style="padding:12px">'.$view.'<script>function fmCsrfToken(){return "test-token";}</script><script src="assets/js/activacion.js"></script><script>'.file_get_contents(__DIR__.'/weekly-schedule.js').'</script></body></html>';
$path=sys_get_temp_dir().'/fmglobal-weekly-ui.html';file_put_contents($path,$html);echo $path.PHP_EOL;