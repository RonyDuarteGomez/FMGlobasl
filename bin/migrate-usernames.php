<?php
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require_once dirname(__DIR__).'/bootstrap/app.php';
$config=require FM_ROOT.'/config/database.php';
$check=in_array('--check',$argv,true);
if (!$check && (!in_array($config['host'],['localhost','127.0.0.1'],true)||!str_ends_with($config['database'],'_local'))&&!in_array('--allow-deployment',$argv,true)) throw new RuntimeException('Usa --allow-deployment fuera de la base local.');
$count=$check?count(\FMGlobal\Repositories\UsernameMigration::plan(database())):\FMGlobal\Repositories\UsernameMigration::apply(database());
echo $count.($check?' usuarios requieren normalización.':' usuarios normalizados; historial conservado por ID.').PHP_EOL;
