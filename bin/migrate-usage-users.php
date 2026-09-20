<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__).'/bootstrap/app.php';
$config = require FM_ROOT.'/config/database.php';
$check = in_array('--check',$argv,true);
if (!$check && (!in_array($config['host'],['localhost','127.0.0.1'],true) || !str_ends_with($config['database'],'_local')) && !in_array('--allow-deployment',$argv,true)) {
    throw new RuntimeException('Usa --allow-deployment fuera de la base local.');
}
$result = $check ? \FMGlobal\Repositories\UsageUserMigration::report(database()) : \FMGlobal\Repositories\UsageUserMigration::apply(database());
echo ($check ? 'Diagnóstico' : 'Migración aplicada').' en '.$config['database'].PHP_EOL;
echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
