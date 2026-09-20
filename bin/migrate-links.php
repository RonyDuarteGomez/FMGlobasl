<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once dirname(__DIR__).'/bootstrap/app.php';
$config=require FM_ROOT.'/config/database.php';
if((!in_array($config['host'],['localhost','127.0.0.1'],true)||!str_ends_with($config['database'],'_local'))&&!in_array('--allow-deployment',$argv,true)) throw new RuntimeException('Usa --allow-deployment fuera de la base local.');
$db=database();
\FMGlobal\Repositories\LinkAccountMigration::apply($db);
$keyPath=FM_ROOT.'/config/links.key';
if(!is_file($keyPath)) {
 if((int)$db->query('SELECT COUNT(*) n FROM fm_link_accounts')->fetch_assoc()['n']>0) throw new RuntimeException('Restaura la clave original: hay cuentas cifradas.');
 $handle=fopen($keyPath,'x');
 if(!$handle) throw new RuntimeException('No se pudo crear la clave.');
 try{if(fwrite($handle,random_bytes(32))!==32)throw new RuntimeException('No se pudo escribir la clave.');}finally{fclose($handle);}
 @chmod($keyPath,0600);
}
new \FMGlobal\Services\Links\AccountVault();
echo "Migración de cuentas aplicada. Conserva config/links.key en un respaldo privado.\n";
