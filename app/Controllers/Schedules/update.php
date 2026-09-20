<?php
$raw=$_POST['schedule']??null;
if(!is_string($raw)||strlen($raw)>20000)throw new \FMGlobal\Http\HttpException(422,'Programación no válida.');
try{$week=json_decode($raw,true,32,JSON_THROW_ON_ERROR);}catch(\JsonException $e){throw new \FMGlobal\Http\HttpException(422,'Programación no válida.');}
$revision=filter_var($_POST['revision']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>0]]);
if($revision===false||$revision===null)throw new \FMGlobal\Http\HttpException(422,'Versión de programación no válida.');
$saved=(new \FMGlobal\Repositories\PublicScheduleRepository(database()))->save($week,$revision,$_SESSION['usuario']);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success'=>true,'revision'=>$saved['revision'],'active'=>\FMGlobal\Services\Schedules\WeeklySchedule::active($saved['week']),'message'=>'Programación guardada.']);