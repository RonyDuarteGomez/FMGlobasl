<?php
namespace FMGlobal\Services\Schedules;
use FMGlobal\Http\HttpException;
use FMGlobal\Services\Users\ScheduleService;
final class WeeklySchedule
{
    public static function legacy(array $rows): array
    {
        $week=array_fill(1,7,['mode'=>'all','slots'=>[]]);$seen=[];
        foreach($rows as $row){
            $day=array_search(strtr(mb_strtoupper(trim($row['dia'])),['É'=>'E','Á'=>'A']),ScheduleService::DAYS,true);
            if($day===false || isset($seen[$day]))continue;
            $seen[$day]=true;
            // The legacy public gate only considered whole hours, not minutes.
            $start=(int)substr($row['hora_inicio'],0,2)*60;$end=(int)substr($row['hora_fin'],0,2)*60;
            if($start>=$end)continue;
            $slots=[];
            if($start>0)$slots[]=['start'=>'00:00','end'=>self::time($start)];
            if($end<1440)$slots[]=['start'=>self::time($end),'end'=>'24:00'];
            $week[$day]=['mode'=>$slots?'hours':'off','slots'=>$slots];
        }
        return $week;
    }
    public static function time(int $minute): string { return sprintf('%02d:%02d',intdiv($minute,60),$minute%60); }
    public static function minute(mixed $value,bool $end=false): int
    {
        if($end && $value==='24:00')return 1440;
        if(!is_string($value)||!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D',$value))throw new HttpException(422,'Usa horas válidas en formato HH:MM; 24:00 solo puede ser hora final.');
        return (int)substr($value,0,2)*60+(int)substr($value,3,2);
    }
    public static function validate(mixed $input): array
    {
        if(!is_array($input)||count($input)!==7)throw new HttpException(422,'Envía la programación de los siete días.');
        $week=[];
        for($day=1;$day<=7;$day++){
            $item=$input[$day]??null;
            if(!is_array($item)||!in_array($item['mode']??null,['all','off','hours'],true))throw new HttpException(422,'Selecciona la disponibilidad de cada día.');
            $slots=$item['slots']??[];
            if(!is_array($slots)||count($slots)>6)throw new HttpException(422,'Puedes definir hasta seis horarios por día.');
            if($item['mode']!=='hours'){ $week[$day]=['mode'=>$item['mode'],'slots'=>[]];continue; }
            if(!$slots)throw new HttpException(422,'Agrega al menos un horario en '.ScheduleService::DAYS[$day].'.');
            $normalized=[];
            foreach($slots as $slot){
                if(!is_array($slot))throw new HttpException(422,'Horario no válido.');
                $start=self::minute($slot['start']??null);$end=self::minute($slot['end']??null,true);
                if($start>=$end)throw new HttpException(422,'La hora final debe ser posterior a la inicial en '.ScheduleService::DAYS[$day].'. Para cruzar medianoche, usa también el día siguiente.');
                $normalized[]=['start'=>self::time($start),'end'=>self::time($end)];
            }
            usort($normalized,fn($a,$b)=>strcmp($a['start'],$b['start']));$last=-1;
            foreach($normalized as $slot){$start=self::minute($slot['start']);if($start<$last)throw new HttpException(422,'Hay horarios superpuestos en '.ScheduleService::DAYS[$day].'.');$last=self::minute($slot['end'],true);}
            $week[$day]=['mode'=>'hours','slots'=>$normalized];
        }
        return $week;
    }
    public static function active(array $week,?\DateTimeImmutable $now=null): bool
    {
        $now=($now??new \DateTimeImmutable('now'))->setTimezone(new \DateTimeZone('America/Lima'));
        $day=$week[(int)$now->format('N')];
        if($day['mode']!=='hours')return $day['mode']==='all';
        $minute=(int)$now->format('H')*60+(int)$now->format('i');
        foreach($day['slots'] as $slot)if($minute>=self::minute($slot['start'])&&$minute<self::minute($slot['end'],true))return true;
        return false;
    }
}