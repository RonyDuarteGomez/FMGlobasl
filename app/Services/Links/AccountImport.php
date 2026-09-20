<?php
namespace FMGlobal\Services\Links;
use FMGlobal\Http\HttpException;
use FMGlobal\Repositories\LinkAccountRepository;
final class AccountImport {
 public function __construct(private LinkAccountRepository $accounts){}
 public function run(int $actor,string $path):array {
  if(!$this->accounts->actor($actor)['admin'])throw new HttpException(403,'Acción exclusiva del administrador.');
  if(filesize($path)>5*1024*1024)throw new HttpException(422,'El CSV no debe superar 5 MB.');
  $handle=fopen($path,'r');if(!$handle)throw new HttpException(422,'No se pudo leer el CSV.');
  try {
   $first=fgets($handle);if($first===false)throw new HttpException(422,'El CSV está vacío.');
   $first=preg_replace('/^\xEF\xBB\xBF/','',$first);$delimiter=str_contains($first,';')?';':',';
   $header=str_getcsv(trim($first),$delimiter,'"','');
   if($header!==['ID','secure','correo_contrasena'])throw new HttpException(422,'Encabezados esperados: ID, secure, correo_contrasena. Descarga el modelo.');
   $rows=[];$line=1;
   while(($row=fgetcsv($handle,0,$delimiter,'"',''))!==false){$line++;if($row===[null])continue;if(count($rows)>=5000)throw new HttpException(422,'Máximo 5000 cuentas por archivo.');$rows[]=[$line,$row];}
  }finally{fclose($handle);}
  $loaded=0;$errors=[];
  foreach($rows as [$line,$row]){
   try{if(count($row)!==3||!mb_check_encoding(implode('',$row),'UTF-8'))throw new HttpException(422,'Se requieren tres columnas en UTF-8.');$this->accounts->save($actor,['external_id'=>$row[0],'secure'=>$row[1],'credentials'=>$row[2]]);$loaded++;}
   catch(HttpException $e){if(!in_array($e->status,[409,422],true))throw $e;$errors[]=['row'=>$line,'reason'=>$e->getMessage(),'email'=>explode(':',(string)($row[2]??''),2)[0],'data'=>$row];}
  }
  return ['loaded'=>$loaded,'rejected'=>count($errors),'errors'=>$errors];
 }
}
