<?php
namespace FMGlobal\Repositories;
final class ScheduleRepository
{
    public function __construct(private \mysqli $db) {}
    public function forUser(int $id): array { return $this->db->execute_query('SELECT dia,hora_inicio,hora_fin FROM activacion WHERE usuario_id=?',[$id])->fetch_all(MYSQLI_ASSOC); }
    public function publicSettings(): \mysqli_result { return $this->db->query("SELECT id,dia,hora_inicio,hora_fin FROM activacion WHERE usuario_id=0 ORDER BY FIELD(dia,'LUNES','MARTES','MIERCOLES','JUEVES','VIERNES','SABADO','DOMINGO')"); }
    public function updatePublic(int $id, string $day, string $start, string $end, string $actor): void
    {
        $row=$this->db->execute_query('SELECT id FROM activacion WHERE id=? AND usuario_id=0 AND dia=?',[$id,$day])->fetch_assoc();
        if (!$row) throw new \FMGlobal\Http\HttpException(404,'Horario no encontrado.');
        $this->db->execute_query('UPDATE activacion SET hora_inicio=?,hora_fin=?,usuario_registro=?,fecha_registro=NOW() WHERE id=? AND usuario_id=0 AND dia=?',[$start,$end,$actor,$id,$day]);
    }
    public function saveDay(int $id, string $day, string $start, string $end, string $actor): void
    {
        $row=$this->db->execute_query('SELECT id FROM activacion WHERE usuario_id=? AND dia=?',[$id,$day])->fetch_assoc();
        if ($row) $this->db->execute_query('UPDATE activacion SET hora_inicio=?,hora_fin=?,usuario_registro=?,fecha_registro=NOW() WHERE usuario_id=? AND dia=?',[$start,$end,$actor,$id,$day]);
        else $this->db->execute_query('INSERT INTO activacion(hora_inicio,hora_fin,dia,usuario_registro,fecha_registro,usuario_id) VALUES(?,?,?,?,NOW(),?)',[$start,$end,$day,$actor,$id]);
    }
}
