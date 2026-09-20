<?php
namespace FMGlobal\Repositories;
final class ReportRepository {
 public function __construct(private \mysqli $db) {}
 public function publicDetails(): \mysqli_result { return $this->db->query('
SELECT 
  DATE(us.fecha) AS fecha_consulta,
  us.correo AS correo_consultado,
  us.num_urls AS n_codigos,
  CASE WHEN streaming = \'1\' THEN \'Netflix\' ELSE \'Disney\'END AS usuario
FROM uso_servicio us
LEFT JOIN usuarios usu ON us.usuario_id = usu.id
LEFT JOIN personal pe ON usu.id = pe.usuario_id
where streaming in (\'1\',\'2\')
ORDER BY us.fecha DESC;

'); }
 public function dailyTotals(): \mysqli_result { return $this->db->query('
SELECT 
  DATE(fecha) AS fecha,
  COUNT(*) AS total_consultas
FROM uso_servicio
where streaming in (\'1\',\'2\')
GROUP BY DATE(fecha)
ORDER BY fecha ASC
'); }
 public function internalDetails(): \mysqli_result { return $this->db->query('
SELECT 
  DATE(us.fecha) AS fecha_consulta,
  us.correo AS correo_consultado,
  us.num_urls AS n_codigos,
  CONCAT(
      UCASE(LEFT(pe.nombre,1)), LCASE(SUBSTRING(pe.nombre,2)), \' \',
      UCASE(LEFT(pe.apellido_paterno,1)), LCASE(SUBSTRING(pe.apellido_paterno,2)), \' \',
      UCASE(LEFT(pe.apellido_materno,1)), LCASE(SUBSTRING(pe.apellido_materno,2))
    ) as usuario,
  CASE 
    WHEN us.streaming = \'3\' THEN \'Netflix (Estoy de Viaje)\'
    WHEN us.streaming = \'4\' THEN \'Disney (Acceso Unico)\'
    WHEN us.streaming = \'5\' THEN \'Netflix (Inicio Session)\'
    WHEN us.streaming = \'6\' THEN \'Soporte\'
    ELSE \'N/D\'
  END AS servicio
FROM uso_servicio us
LEFT JOIN usuarios usu ON us.usuario_id = usu.id
LEFT JOIN personal pe ON usu.id = pe.usuario_id
WHERE us.streaming IN (\'3\',\'4\',\'5\',\'6\')
ORDER BY us.fecha DESC
'); }
}

