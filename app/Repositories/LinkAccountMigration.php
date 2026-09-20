<?php
namespace FMGlobal\Repositories;
final class LinkAccountMigration {
 public static function apply(\mysqli $db): void {
  $db->query("CREATE TABLE IF NOT EXISTS fm_link_accounts (
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
   external_id TEXT NOT NULL, secure_value TEXT NOT NULL, credentials TEXT NOT NULL,
   credential_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
   status VARCHAR(10) NOT NULL DEFAULT 'active', assigned_user_id INT NULL,
   assigned_at DATETIME NULL, moved_at DATETIME NULL, moved_by INT NULL,
   revision INT NOT NULL DEFAULT 1, generation_token CHAR(32) NULL, generation_until DATETIME NULL,
   created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
   INDEX assigned_status(assigned_user_id,status)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  $db->query("CREATE TABLE IF NOT EXISTS fm_link_audit (
   id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, account_id BIGINT UNSIGNED NOT NULL,
   actor_id INT NOT NULL, action VARCHAR(30) NOT NULL, source_user_id INT NULL,
   target_user_id INT NULL, result VARCHAR(20) NULL, created_at DATETIME NOT NULL,
   INDEX account_history(account_id,created_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  // 0: sin error, 1: no devuelve link, 2: error marcado manualmente.
  if (!$db->query("SHOW COLUMNS FROM fm_link_accounts LIKE 'error_type'")->num_rows) {
   $db->query('ALTER TABLE fm_link_accounts ADD COLUMN error_type TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER status');
  }
  // Reconstruye errores históricos según el último evento que cambió el estado.
  $db->query("UPDATE fm_link_accounts a SET error_type=CASE WHEN
   (SELECT h.action FROM fm_link_audit h WHERE h.account_id=a.id AND h.action IN ('manual_error','generate') ORDER BY h.id DESC LIMIT 1)='manual_error'
   THEN 2 ELSE 1 END WHERE a.status='error' AND a.error_type=0");
 }
}
