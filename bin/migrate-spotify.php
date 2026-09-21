<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__).'/bootstrap/app.php';
$config=require FM_ROOT.'/config/database.php';
if((!in_array($config['host'],['localhost','127.0.0.1'],true)||!str_ends_with($config['database'],'_local'))&&!in_array('--allow-deployment',$argv,true))throw new RuntimeException('Usa --allow-deployment fuera de la base local.');
new \FMGlobal\Services\Links\AccountVault();
\FMGlobal\Repositories\PermissionMigration::apply(database());
\FMGlobal\Repositories\SpotifyMigration::apply(database());
echo "Spotify preparado; permiso inicial solo para administrador. Conservar config/links.key para las contraseñas cifradas.\n";
