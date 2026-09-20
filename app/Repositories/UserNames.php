<?php
namespace FMGlobal\Repositories;
final class UserNames {
 public static function all(\mysqli $db):array {
  $rows=$db->query("SELECT u.id,u.usuario,COALESCE(NULLIF(TRIM(CONCAT_WS(' ',NULLIF(p.nombre,''),NULLIF(p.apellido_paterno,''),NULLIF(p.apellido_materno,''))),''),u.usuario) display_name FROM usuarios u LEFT JOIN personal p ON p.usuario_id=u.id")->fetch_all(MYSQLI_ASSOC);
  return array_column($rows,null,'id');
 }
}
