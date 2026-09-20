<?php
if (PHP_SAPI!=='cli') exit;
require dirname(__DIR__).'/bootstrap/app.php';
use FMGlobal\Repositories\{UsageUserMigration,UsageRepository,ActivityRepository};
$config=require FM_ROOT.'/config/database.php';
if (!in_array($config['host'],['localhost','127.0.0.1'],true)||!str_ends_with($config['database'],'_local')) throw new RuntimeException('Solo pruebas locales.');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
$db=new mysqli($config['host'],$config['user'],$config['password']);
$name='fmglobal_test_usage_'.bin2hex(random_bytes(5));
$count=0;
function checkUsage(bool $ok,string $label):void {global $count;if(!$ok)throw new RuntimeException($label);$count++;echo "OK $label\n";}
try {
 $db->query("CREATE DATABASE `$name` CHARACTER SET utf8mb4");$db->select_db($name);
 $db->query('CREATE TABLE usuarios(id INT PRIMARY KEY,usuario VARCHAR(50)) ENGINE=InnoDB');
 $db->query('CREATE TABLE personal(usuario_id INT,nombre VARCHAR(50),apellido_paterno VARCHAR(50),apellido_materno VARCHAR(50)) ENGINE=InnoDB');
 $db->query('CREATE TABLE uso_servicio(id INT AUTO_INCREMENT PRIMARY KEY,correo VARCHAR(255),num_urls INT,fecha DATETIME,usuario VARCHAR(100),streaming INT) ENGINE=InnoDB');
 $db->query("INSERT INTO usuarios VALUES(10,'asesor'),(20,'soporte'),(30,'duplicado'),(40,'duplicado')");
 $db->query("INSERT INTO personal VALUES(10,'Ana','Ramirez','Diaz'),(20,'Luis','Perez','')");
 foreach(['asesor','soporte',null,'','desconocido','duplicado','ASESOR'] as $user)$db->execute_query("INSERT INTO uso_servicio(correo,num_urls,fecha,usuario,streaming) VALUES('historical@example.test',0,NOW(),?,3)",[$user]);
 $before=UsageUserMigration::report($db);
 checkUsage(!UsageUserMigration::hasColumn($db)&&(int)$before['total']===7,'Diagnóstico no modifica esquema');
 $first=UsageUserMigration::apply($db);
 checkUsage((int)$first['total']===7&&(int)$first['linked']===2&&(int)$first['anonymous']===2&&(int)$first['unresolved']===3,'Backfill exacto; conserva anónimos, ambiguos y no coincidentes');
 checkUsage(UsageUserMigration::apply($db)===$first,'Migración repetible sin duplicar ni alterar datos');
 $activity=new ActivityRepository($db);
 checkUsage((int)$activity->summary([3],10)['total']===1,'ID limita historial propio');
 $db->query("UPDATE usuarios SET usuario='nuevo_login' WHERE id=10");
 $db->query("INSERT INTO usuarios VALUES(50,'asesor'),(60,'desconocido')");
 UsageUserMigration::apply($db);
 checkUsage((int)$activity->summary([3],10)['total']===1 && (int)$activity->summary([3],50)['total']===0,'Renombrar y reutilizar login no traslada historial');
 checkUsage((int)$activity->summary([3],60)['total']===0,'Reejecución no atribuye pendientes a usuarios nuevos');
 $rows=$activity->searchReport([3],10,30,'Ana',1,10);
 checkUsage($rows['total']===1&&$rows['rows'][0]['display_name']==='Ana Ramirez Diaz','Nombre visible se obtiene mediante ID después del cambio de login');
 $usage=new UsageRepository($db);$usage->register('new@example.test',0,10,6);$usage->register('anonymous@example.test',1,null,1);
 $row=$db->query("SELECT usuario,usuario_id FROM uso_servicio WHERE correo='new@example.test'")->fetch_assoc();
 checkUsage((int)$row['usuario_id']===10&&$row['usuario']==='nuevo_login','Nuevas consultas guardan ID y snapshot actual');
 checkUsage($db->query("SELECT usuario_id FROM uso_servicio WHERE correo='anonymous@example.test'")->fetch_assoc()['usuario_id']===null,'Consulta anónima conserva ID nulo');
 try{$usage->register('invalid@example.test',0,999,6);throw new RuntimeException('Aceptó identidad inexistente');}catch(InvalidArgumentException $e){checkUsage(true,'Rechaza ID inexistente');}
 checkUsage((int)$db->query('SELECT COUNT(*) n FROM uso_servicio')->fetch_assoc()['n']===9,'No elimina registros ni registra intentos con identidad inválida');
 $db->query("INSERT INTO usuarios VALUES(70,'con espacios')");
 checkUsage(count(\FMGlobal\Repositories\UsernameMigration::plan($db))===1,'Diagnóstico detecta normalización pendiente');
 checkUsage(\FMGlobal\Repositories\UsernameMigration::apply($db)===1 && \FMGlobal\Repositories\UsernameMigration::apply($db)===0,'Normalización conserva ID y es repetible');
 $db->query("INSERT INTO usuarios VALUES(80,'con espacios')");
 try {\FMGlobal\Repositories\UsernameMigration::apply($db);throw new LogicException('Aceptó duplicidad');}catch(RuntimeException $e){checkUsage(str_contains($e->getMessage(),'duplicidad'),'Normalización aborta por colisión sin modificar usuarios');}
 echo "$count pruebas correctas.\n";
} finally {$db->query("DROP DATABASE IF EXISTS `$name`");$db->close();}
