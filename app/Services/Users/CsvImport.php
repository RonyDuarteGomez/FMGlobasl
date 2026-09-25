<?php
namespace FMGlobal\Services\Users;
use FMGlobal\Http\HttpException;
use FMGlobal\Repositories\{UserRepository,ScheduleRepository};
final class CsvImport {
 public const COLUMNS=['nombre','apellido_paterno','apellido_materno','correo','telefono','usuario','clave','perfil'];
 public function __construct(private \mysqli $db){}
 public function import(int $actor,string $actorName,string $text):array {
  $users=new UserRepository($this->db);$identity=$users->identity($actor);$permissions=$users->permissions($actor);
  if(!$identity||(int)$identity['estado']!==1||empty($permissions['users.manage'])||empty($permissions['permissions.manage']))throw new HttpException(403,'Importar usuarios y asignar perfiles requiere permisos de Usuarios y Permisos usuarios.');
  if(strlen($text)>2097152||!mb_check_encoding($text,'UTF-8'))throw new HttpException(422,'Usa CSV UTF-8 de hasta 2 MB.');
  $text=preg_replace('/^\xEF\xBB\xBF/','',$text);$stream=fopen('php://temp','r+');fwrite($stream,$text);rewind($stream);
  try {
   $first=strtok($text,"\n");$sep=substr_count($first?:'',';')>substr_count($first?:'',',')?';':',';
   if(fgetcsv($stream,0,$sep,'"','')!==self::COLUMNS)throw new HttpException(422,'Las columnas deben coincidir con el CSV modelo: '.implode(',',self::COLUMNS).'.');
   $rows=[];$line=1;while(($values=fgetcsv($stream,0,$sep,'"',''))!==false){$line++;if($values===[null])continue;$rows[]=['line'=>$line,'values'=>$values];if(count($rows)>500)throw new HttpException(422,'Carga hasta 500 filas por archivo.');}
   if(!$rows)throw new HttpException(422,'El CSV no contiene datos.');
  }finally{fclose($stream);}
  $roles=[];foreach($users->roles() as $role)$roles[mb_strtolower(trim($role['rol_nombre']))]=(int)$role['rol_id'];
  $service=new UserService($users,new ScheduleRepository($this->db));$imported=0;$errors=[];
  foreach($rows as $row){$values=$row['values'];try{
   if(count($values)!==count(self::COLUMNS))throw new HttpException(422,'Cantidad de columnas incorrecta.');
   $input=array_combine(self::COLUMNS,$values);$role=$roles[mb_strtolower(trim($input['perfil']))]??null;
   if(!$role)throw new HttpException(422,'El perfil no existe. Usa el nombre que aparece en Cargo.');
   $input['rol']=(string)$role;unset($input['perfil']);$service->save($input,$actorName,$actor);$imported++;
  }catch(HttpException $e){if(!in_array($e->status,[409,422],true))throw $e;
   // El reporte identifica la fila sin volver a distribuir contraseñas.
   $safe=array_slice($values,0,count(self::COLUMNS));$safe[6]='[omitida]';
   $errors[]=['line'=>$row['line'],'values'=>$safe,'reason'=>$e->getMessage()];
  }}
  return ['imported'=>$imported,'errors'=>$errors];
 }
}
