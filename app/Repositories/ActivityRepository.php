<?php
namespace FMGlobal\Repositories;
final class ActivityRepository
{
    public function __construct(private \mysqli $db) {}
    private function filter(array $operations,?int $user,int $days): array
    {
        if (!$operations) return ['1=0',[]];
        $ids=implode(',',array_map('intval',$operations));
        $sql="streaming IN ($ids)"; $args=[];
        if ($user!==null) { $sql.=' AND usuario_id=?'; $args[]=$user; }
        if ($days>0) { $sql.=' AND fecha>=?'; $args[]=(new \DateTimeImmutable('today'))->modify('-'.($days-1).' days')->format('Y-m-d'); }
        return [$sql,$args];
    }
    public function summary(array $operations,?int $user,int $days=30): array
    {
        [$where,$args]=$this->filter($operations,$user,$days);
        return $this->db->execute_query("SELECT COUNT(*) AS total,COALESCE(SUM(DATE(fecha)=CURDATE()),0) AS today,COALESCE(SUM(num_urls),0) AS results FROM (SELECT log.*,COALESCE((SELECT NULLIF(TRIM(CONCAT_WS(' ',p.nombre,p.apellido_paterno,p.apellido_materno)),'') FROM usuarios u INNER JOIN personal p ON p.usuario_id=u.id WHERE u.id=log.usuario_id LIMIT 1),log.usuario) display_name FROM uso_servicio log) activity WHERE $where",$args)->fetch_assoc();
    }
    public function daily(array $operations,?int $user,int $days=30): array
    {
        [$where,$args]=$this->filter($operations,$user,$days);
        $rows=$this->db->execute_query("SELECT DATE(fecha) AS day,COUNT(*) AS total FROM (SELECT log.*,COALESCE((SELECT NULLIF(TRIM(CONCAT_WS(' ',p.nombre,p.apellido_paterno,p.apellido_materno)),'') FROM usuarios u INNER JOIN personal p ON p.usuario_id=u.id WHERE u.id=log.usuario_id LIMIT 1),log.usuario) display_name FROM uso_servicio log) activity WHERE $where GROUP BY DATE(fecha) ORDER BY day",$args)->fetch_all(MYSQLI_ASSOC);
        $totals=array_column($rows,'total','day'); $series=[];
        for ($i=$days-1;$i>=0;$i--) { $day=(new \DateTimeImmutable('today'))->modify("-$i days")->format('Y-m-d'); $series[$day]=(int)($totals[$day]??0); }
        return $series;
    }
    public function details(array $operations,?int $user,int $days=30,int $page=1): array
    {
        [$where,$args]=$this->filter($operations,$user,$days);
        $offset=(max(1,$page)-1)*50;
        return $this->db->execute_query("SELECT fecha,correo,num_urls,usuario,display_name,streaming FROM (SELECT log.*,COALESCE((SELECT NULLIF(TRIM(CONCAT_WS(' ',p.nombre,p.apellido_paterno,p.apellido_materno)),'') FROM usuarios u INNER JOIN personal p ON p.usuario_id=u.id WHERE u.id=log.usuario_id LIMIT 1),log.usuario) display_name FROM uso_servicio log) activity WHERE $where ORDER BY fecha DESC,usuario,correo,streaming,num_urls LIMIT 50 OFFSET $offset",$args)->fetch_all(MYSQLI_ASSOC);
    }
    public function dailyByService(array $operations,?int $user,int $days,string $search=''): array
    {
        [$where,$args]=$this->filter($operations,$user,$days);
        if ($search!=='') {
            $where.=" AND (LOCATE(?,correo)>0 OR LOCATE(?,COALESCE(display_name,'Clientes'))>0 OR LOCATE(?,CASE WHEN streaming=1 THEN 'Netflix' WHEN streaming=2 THEN 'Disney+' WHEN streaming=3 THEN 'Netflix · Estoy de viaje' WHEN streaming=4 THEN 'Disney+ · Acceso único' WHEN streaming=5 THEN 'Netflix · Inicio de sesión' WHEN streaming=6 THEN 'Soporte' ELSE '' END)>0)";
            array_push($args,$search,$search,$search);
        }
        $rows=$this->db->execute_query("SELECT DATE(fecha) AS day,streaming,COUNT(*) AS total FROM (SELECT log.*,COALESCE((SELECT NULLIF(TRIM(CONCAT_WS(' ',p.nombre,p.apellido_paterno,p.apellido_materno)),'') FROM usuarios u INNER JOIN personal p ON p.usuario_id=u.id WHERE u.id=log.usuario_id LIMIT 1),log.usuario) display_name FROM uso_servicio log) activity WHERE $where GROUP BY DATE(fecha),streaming ORDER BY day",$args)->fetch_all(MYSQLI_ASSOC);
        $today=new \DateTimeImmutable('today');
        $start=$days>0?$today->modify('-'.($days-1).' days'):new \DateTimeImmutable($rows[0]['day']??$today->format('Y-m-d'));
        $end=$today;
        if ($rows) $end=max($end,new \DateTimeImmutable($rows[count($rows)-1]['day']));
        $series=[];
        for($date=$start;$date<=$end;$date=$date->modify('+1 day')) $series[$date->format('Y-m-d')]=[1=>0,2=>0,3=>0,4=>0,5=>0,6=>0];
        foreach($rows as $row) if(isset($series[$row['day']][(int)$row['streaming']])) $series[$row['day']][(int)$row['streaming']]=(int)$row['total'];
        return $series;
    }
    public function searchReport(array $operations,?int $user,int $days,string $search,int $page,int $size): array
    {
        [$where,$args]=$this->filter($operations,$user,$days);
        if ($search!=='') {
            $where.=" AND (LOCATE(?,correo)>0 OR LOCATE(?,COALESCE(display_name,'Clientes'))>0 OR LOCATE(?,CASE WHEN streaming=1 THEN 'Netflix' WHEN streaming=2 THEN 'Disney+' WHEN streaming=3 THEN 'Netflix · Estoy de viaje' WHEN streaming=4 THEN 'Disney+ · Acceso único' WHEN streaming=5 THEN 'Netflix · Inicio de sesión' WHEN streaming=6 THEN 'Soporte' ELSE '' END)>0)";
            array_push($args,$search,$search,$search);
        }
        $size=in_array($size,[10,20,50],true)?$size:20;
        $total=(int)$this->db->execute_query("SELECT COUNT(*) AS total FROM (SELECT log.*,COALESCE((SELECT NULLIF(TRIM(CONCAT_WS(' ',p.nombre,p.apellido_paterno,p.apellido_materno)),'') FROM usuarios u INNER JOIN personal p ON p.usuario_id=u.id WHERE u.id=log.usuario_id LIMIT 1),log.usuario) display_name FROM uso_servicio log) activity WHERE $where",$args)->fetch_assoc()['total'];
        $pages=max(1,(int)ceil($total/$size)); $page=min(max(1,$page),$pages); $offset=($page-1)*$size;
        $rows=$this->db->execute_query("SELECT fecha,correo,num_urls,usuario,display_name,streaming FROM (SELECT log.*,COALESCE((SELECT NULLIF(TRIM(CONCAT_WS(' ',p.nombre,p.apellido_paterno,p.apellido_materno)),'') FROM usuarios u INNER JOIN personal p ON p.usuario_id=u.id WHERE u.id=log.usuario_id LIMIT 1),log.usuario) display_name FROM uso_servicio log) activity WHERE $where ORDER BY fecha DESC,usuario,correo,streaming,num_urls LIMIT $size OFFSET $offset",$args)->fetch_all(MYSQLI_ASSOC);
        return compact('total','pages','page','rows');
    }
    public function platforms(array $operations, ?int $user): array
    {
        [$where,$args]=$this->filter($operations,$user,30);
        $row=$this->db->execute_query("SELECT COALESCE(SUM(streaming IN (1,3,5)),0) AS netflix, COALESCE(SUM(streaming IN (2,4)),0) AS disney FROM (SELECT log.*,COALESCE((SELECT NULLIF(TRIM(CONCAT_WS(' ',p.nombre,p.apellido_paterno,p.apellido_materno)),'') FROM usuarios u INNER JOIN personal p ON p.usuario_id=u.id WHERE u.id=log.usuario_id LIMIT 1),log.usuario) display_name FROM uso_servicio log) activity WHERE $where",$args)->fetch_assoc();
        return array_map('intval',$row);
    }
    public function users(): array { return $this->db->query('SELECT SUM(estado=1) AS active,SUM(estado<>1) AS inactive FROM usuarios')->fetch_assoc(); }
    public function gmail(): int { return (int)$this->db->query('SELECT COUNT(*) AS total FROM gmail_tokens WHERE activa=1')->fetch_assoc()['total']; }
    public function publicAvailability(): bool { return \FMGlobal\Services\Schedules\WeeklySchedule::active((new PublicScheduleRepository($this->db))->load()['week']); }
    public function schedules(): array { return $this->db->query('SELECT dia,hora_inicio,hora_fin FROM activacion WHERE usuario_id=0 ORDER BY id')->fetch_all(MYSQLI_ASSOC); }
}
