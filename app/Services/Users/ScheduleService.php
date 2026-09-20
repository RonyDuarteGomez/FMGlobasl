<?php
namespace FMGlobal\Services\Users;
use FMGlobal\Http\HttpException;
use FMGlobal\Support\Input;
final class ScheduleService
{
    public const DAYS = [1=>'LUNES',2=>'MARTES',3=>'MIERCOLES',4=>'JUEVES',5=>'VIERNES',6=>'SABADO',7=>'DOMINGO'];
    public function __construct(private \FMGlobal\Repositories\ScheduleRepository $schedules) {}
    public static function hour(mixed $value): string
    {
        if (!is_string($value) || !preg_match('/^(?:[01]?\d|2[0-3])$/D',$value)) throw new HttpException(422,'Hora no válida. Usa un valor de 00 a 23.');
        return str_pad($value,2,'0',STR_PAD_LEFT).':00:00';
    }
    public static function pair(mixed $start, mixed $end): array
    {
        $pair=[self::hour($start),self::hour($end)];
        // Horas iguales conservan el día cerrado. No se introducen turnos nocturnos.
        if ($pair[0]>$pair[1]) throw new HttpException(422,'La hora final no puede ser anterior a la inicial.');
        return $pair;
    }
    public function updatePublic(array $input, string $actor): void
    {
        $id=Input::id($input,'id'); $day=Input::text($input,'dia',15,true);
        if (!in_array($day,self::DAYS,true)) throw new HttpException(422,'Día no válido.');
        [$start,$end]=self::pair($input['hora_inicio']??null,$input['hora_fin']??null);
        $this->schedules->updatePublic($id,$day,$start,$end,$actor);
    }
    public function indexed(int $id): array
    {
        $result=[];
        foreach($this->schedules->forUser($id) as $row) {
            $day=strtr(mb_strtoupper(trim($row['dia'])),['É'=>'E','Á'=>'A']);
            $index=array_search($day,self::DAYS,true);
            if ($index!==false) $result[$index]=['inicio'=>$row['hora_inicio'],'fin'=>$row['hora_fin']];
        }
        return $result;
    }
}
