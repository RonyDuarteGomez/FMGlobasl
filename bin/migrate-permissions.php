<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/bootstrap/app.php';
$config=require FM_ROOT.'/config/database.php';
if (!in_array($config['host'],['localhost','127.0.0.1'],true) || !str_ends_with($config['database'],'_local')) {
    if (!in_array('--allow-deployment',$argv,true)) throw new RuntimeException('Fuera de la base local: ejecutar explícitamente con --allow-deployment al desplegar.');
}
\FMGlobal\Repositories\PermissionMigration::apply(database());
echo 'Migración de permisos aplicada: '.$config['database'].PHP_EOL;
