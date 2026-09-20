<?php
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require_once dirname(__DIR__).'/bootstrap/app.php';

// Default is read-only. Deploy while all web requests and background jobs are paused.
$apply=in_array('--apply',$argv,true);
if ($apply && in_array('--check',$argv,true)) {fwrite(STDERR,"Usa --check o --apply, no ambos.\n");exit(1);}
$config=require FM_ROOT.'/config/database.php';
if ($apply && (!in_array($config['host'],['localhost','127.0.0.1'],true)||!str_ends_with($config['database'],'_local'))&&!in_array('--allow-deployment',$argv,true)) {fwrite(STDERR,"Fuera de la base local se requiere --allow-deployment.\n");exit(1);}
$steps=['migrate-permissions.php','migrate-gmail.php','migrate-public-schedule.php','migrate-links.php','migrate-link-credentials.php','migrate-usage-users.php','migrate-usernames.php'];
$locked=false;
try {
    if (PHP_VERSION_ID<80200) throw new RuntimeException('Se requiere PHP 8.2 o superior.');
    foreach (['mysqli','openssl','mbstring'] as $extension) if (!extension_loaded($extension)) throw new RuntimeException('Falta extensión '.$extension.'.');
    $db=database();
    $tables=array_column($db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM),0);
    foreach (['usuarios','personal','rol','activacion','uso_servicio','gmail_tokens'] as $table) {
        if (!in_array($table,$tables,true)) throw new RuntimeException('Falta tabla base '.$table.'. Este ejecutor actualiza una instalación existente.');
    }
    $keyPath=FM_ROOT.'/config/links.key';
    $hasAccounts=in_array('fm_link_accounts',$tables,true)&&(int)$db->query('SELECT COUNT(*) n FROM fm_link_accounts')->fetch_assoc()['n']>0;
    if ($hasAccounts&&!is_file($keyPath)) throw new RuntimeException('Hay cuentas cifradas: restaura config/links.key original antes de continuar.');
    if (is_file($keyPath)) {
        $vault=new \FMGlobal\Services\Links\AccountVault();
        if ($hasAccounts) {
            $sample=$db->query('SELECT credentials,external_id,secure_value FROM fm_link_accounts LIMIT 1')->fetch_assoc();
            foreach ($sample as $cipher) $vault->decrypt($cipher);
        }
    } elseif (!is_writable(dirname($keyPath))) throw new RuntimeException('config/ debe permitir crear links.key durante la migración.');
    $normalize=count(\FMGlobal\Repositories\UsernameMigration::plan($db));
    echo 'Base destino: '.$config['database'].PHP_EOL;
    echo 'Usuarios por normalizar: '.$normalize.PHP_EOL;
    foreach ($steps as $i=>$step) echo ($i+1).'. '.$step.PHP_EOL;
    if (!$apply) {
        echo 'Diagnóstico previo; no se aplicaron cambios. Para ejecutar usa --apply.'.PHP_EOL;
        echo json_encode(\FMGlobal\Repositories\UsageUserMigration::report($db),JSON_PRETTY_PRINT).PHP_EOL;
        exit(0);
    }
    $lock='fm_deploy_'.substr(hash('sha256',$config['database']),0,40);
    $locked=(int)$db->execute_query('SELECT GET_LOCK(?,0) acquired',[$lock])->fetch_assoc()['acquired']===1;
    if (!$locked) throw new RuntimeException('Otra migración está en ejecución.');
    foreach ($steps as $step) {
        echo 'Ejecutando '.$step.PHP_EOL;
        (static function(string $file,array $argv):void {require $file;})(__DIR__.'/'.$step,$argv);
    }
    echo 'Migraciones completadas. Validar la aplicación antes de reabrir el tráfico.'.PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR,'Migración detenida: '.$e->getMessage().PHP_EOL.'Los pasos ya completados se conservan; corregir la causa y reejecutar en mantenimiento.'.PHP_EOL);
    exit(1);
} finally {
    if ($locked) $db->execute_query('SELECT RELEASE_LOCK(?)',[$lock]);
}
