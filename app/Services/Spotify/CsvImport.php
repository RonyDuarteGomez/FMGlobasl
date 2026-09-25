<?php
namespace FMGlobal\Services\Spotify;
use FMGlobal\Http\HttpException;
final class CsvImport {
 public const HEADER=['servicio','correo_principal','correo_pago','proximo_pago','correo_secundario','contrasena','perfil','estado_cuenta','asesor_usuario','cliente_nombre','cliente_celular','inicio_servicio','vencimiento_servicio','ultima_renovacion'];
 public static function parse(string $text):array {
  if(strlen($text)>2097152||!mb_check_encoding($text,'UTF-8'))throw new HttpException(422,'Usa CSV UTF-8 de hasta 2 MB.');
  $text=preg_replace('/^\xEF\xBB\xBF/','',$text);$stream=fopen('php://temp','r+');fwrite($stream,$text);rewind($stream);
  try {
   $first=strtok($text,"\n");$sep=substr_count($first?:'', ';')>substr_count($first?:'', ',')?';':',';
   $header=fgetcsv($stream,0,$sep,'"','');
   if($header!==self::HEADER)throw new HttpException(422,'Las columnas deben coincidir con el CSV modelo, en el mismo orden.');
   $rows=[];$line=1;
   while(($values=fgetcsv($stream,0,$sep,'"',''))!==false){$line++;if($values===[null])continue;
    if(count($values)!==count(self::HEADER)){$rows[]=['_line'=>$line,'_values'=>$values,'_parse_error'=>'Cantidad de columnas incorrecta: se requieren '.count(self::HEADER).'.'];if(count($rows)>500)throw new HttpException(422,'Carga hasta 500 filas por archivo.');continue;}
    $row=array_combine(self::HEADER,$values);foreach($row as $k=>$v)if($k!=='contrasena')$row[$k]=trim($v);$row['_line']=$line;$row['_values']=$values;$rows[]=$row;
    if(count($rows)>500)throw new HttpException(422,'Carga hasta 500 filas por archivo.');
   }
   if(!$rows)throw new HttpException(422,'El CSV no contiene datos.');return $rows;
  }finally{fclose($stream);}
 }
}
