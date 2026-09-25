<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__).'/bootstrap/app.php';
$config=require FM_ROOT.'/config/database.php';
if((!in_array($config['host'],['localhost','127.0.0.1'],true)||!str_ends_with($config['database'],'_local'))&&!in_array('--allow-deployment',$argv,true))throw new RuntimeException('Usa --allow-deployment fuera de la base local.');
\FMGlobal\Repositories\SpotifyIdentityMigration::apply(database());
echo "Identidad Spotify actualizada: principal + pago + secundaria. IDs e historial conservados.\n";
