<?php
namespace FMGlobal\Repositories;
use FMGlobal\Services\Schedules\WeeklySchedule;
use FMGlobal\Http\HttpException;
final class PublicScheduleRepository
{
    public function __construct(private \mysqli $db) {}
    public static function install(\mysqli $db): void
    {
        $db->query("CREATE TABLE IF NOT EXISTS fm_public_schedule(id TINYINT PRIMARY KEY, revision INT NOT NULL DEFAULT 0, schedule_json LONGTEXT NOT NULL, updated_by VARCHAR(100) NOT NULL, updated_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $week=WeeklySchedule::legacy($db->query('SELECT dia,hora_inicio,hora_fin FROM activacion WHERE usuario_id=0 ORDER BY id')->fetch_all(MYSQLI_ASSOC));
        $db->execute_query("INSERT IGNORE INTO fm_public_schedule(id,revision,schedule_json,updated_by,updated_at) VALUES(1,0,?,'migration',NOW())",[json_encode($week,JSON_THROW_ON_ERROR)]);
    }
    public function load(): array
    {
        try {$row=$this->db->query('SELECT revision,schedule_json,updated_at FROM fm_public_schedule WHERE id=1')->fetch_assoc();}
        catch(\mysqli_sql_exception $error){if($error->getCode()!==1146)throw $error;$row=null;}
        if($row)return ['revision'=>(int)$row['revision'],'week'=>WeeklySchedule::validate(json_decode($row['schedule_json'],true,512,JSON_THROW_ON_ERROR)),'ready'=>true];
        return ['revision'=>0,'week'=>WeeklySchedule::legacy($this->db->query('SELECT dia,hora_inicio,hora_fin FROM activacion WHERE usuario_id=0 ORDER BY id')->fetch_all(MYSQLI_ASSOC)),'ready'=>false];
    }
    public function save(mixed $week,int $revision,string $actor): array
    {
        $week=WeeklySchedule::validate($week);
        if(!$this->load()['ready'])throw new HttpException(503,'Primero aplica la migración de programación semanal.');
        $this->db->execute_query('UPDATE fm_public_schedule SET schedule_json=?,revision=revision+1,updated_by=?,updated_at=NOW() WHERE id=1 AND revision=?',[json_encode($week,JSON_THROW_ON_ERROR),$actor,$revision]);
        if($this->db->affected_rows!==1)throw new HttpException(409,'Otra persona modificó la programación. Recarga la página antes de guardar.');
        return ['revision'=>$revision+1,'week'=>$week,'ready'=>true];
    }
}