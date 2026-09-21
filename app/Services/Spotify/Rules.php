<?php
namespace FMGlobal\Services\Spotify;
use FMGlobal\Http\HttpException;
final class Rules {
 public static function text(mixed $value,int $max,bool $required=true):string {
  if(!is_string($value))throw new HttpException(422,'Texto no válido.');$value=trim($value);
  if(($required&&$value==='')||mb_strlen($value)>$max||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$value))throw new HttpException(422,'Completa los campos y respeta su longitud.');return $value;
 }
 public static function email(mixed $value):string {$value=mb_strtolower(self::text($value,254));if(!filter_var($value,FILTER_VALIDATE_EMAIL))throw new HttpException(422,'Correo no válido.');return $value;}
 public static function phone(mixed $value):string {
  $value=self::text($value,40);$value=preg_replace('/[\s().-]/u','',$value);
  if(!preg_match('/^\+[1-9][0-9]{6,14}$/D',$value))throw new HttpException(422,'Indica celular internacional con + y código de país.');return $value;
 }
 public static function date(mixed $value):string {
  if(!is_string($value))throw new HttpException(422,'Fecha no válida.');$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value);
  if(!$date||$date->format('Y-m-d')!==$value||(int)$date->format('Y')<1900||(int)$date->format('Y')>9998)throw new HttpException(422,'Fecha no válida.');return $value;
 }
 public static function month(string $date):string {$day=new \DateTimeImmutable(self::date($date));$next=$day->modify('first day of next month');return $next->setDate((int)$next->format('Y'),(int)$next->format('m'),min((int)$day->format('d'),(int)$next->format('t')))->format('Y-m-d');}
 public static function days(?string $date):?int{return $date===null?null:(int)(new \DateTimeImmutable('today'))->diff(new \DateTimeImmutable($date))->format('%r%a');}
 public static function id(mixed $value):int {$id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false)throw new HttpException(422,'Identificador no válido.');return $id;}
 public static function password(mixed $value):string {if(!is_string($value)||trim($value)===''||strlen($value)>1024||preg_match('/[\x00-\x1F]/',$value))throw new HttpException(422,'La contraseña es obligatoria (máximo 1024 caracteres).');return $value;}
}
