<?php
namespace FMGlobal\Repositories;
final class SpotifyMigration
{
 public static function apply(\mysqli $db):void {
  foreach([
   "CREATE TABLE IF NOT EXISTS fm_service_types(id INT AUTO_INCREMENT PRIMARY KEY,code VARCHAR(40) NOT NULL UNIQUE,name VARCHAR(80) NOT NULL,active TINYINT NOT NULL DEFAULT 1,max_accounts INT NOT NULL DEFAULT 5,max_profiles INT NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_service_mains(id INT AUTO_INCREMENT PRIMARY KEY,service_id INT NOT NULL,email VARCHAR(254) NOT NULL,payment_email VARCHAR(254) NOT NULL,next_payment DATE NOT NULL,revision INT NOT NULL DEFAULT 1,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,UNIQUE(service_id,email),FOREIGN KEY(service_id) REFERENCES fm_service_types(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_service_accounts(id INT AUTO_INCREMENT PRIMARY KEY,main_id INT NOT NULL,email VARCHAR(254) NOT NULL UNIQUE,password_cipher TEXT NOT NULL,state VARCHAR(12) NOT NULL DEFAULT 'enabled',revision INT NOT NULL DEFAULT 1,fallen_reason VARCHAR(500) NULL,created_at DATETIME NOT NULL,updated_at DATETIME NOT NULL,FOREIGN KEY(main_id) REFERENCES fm_service_mains(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_service_profiles(id INT AUTO_INCREMENT PRIMARY KEY,account_id INT NOT NULL,name VARCHAR(80) NOT NULL,current_assignment_id INT NULL UNIQUE,FOREIGN KEY(account_id) REFERENCES fm_service_accounts(id),INDEX(account_id,current_assignment_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_clients(phone VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,name VARCHAR(150) NOT NULL,created_by INT NOT NULL,created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_service_assignments(id INT AUTO_INCREMENT PRIMARY KEY,profile_id INT NOT NULL,client_phone VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,advisor_id INT NOT NULL,start_date DATE NOT NULL,end_date DATE NOT NULL,last_renewed_at DATETIME NULL,closed_at DATETIME NULL,close_reason VARCHAR(20) NULL,revision INT NOT NULL DEFAULT 1,created_by INT NOT NULL,created_at DATETIME NOT NULL,FOREIGN KEY(profile_id) REFERENCES fm_service_profiles(id),FOREIGN KEY(client_phone) REFERENCES fm_clients(phone) ON UPDATE CASCADE,INDEX(advisor_id,closed_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_service_renewals(id BIGINT AUTO_INCREMENT PRIMARY KEY,assignment_id INT NOT NULL,previous_end DATE NOT NULL,new_end DATE NOT NULL,actor_id INT NOT NULL,created_at DATETIME NOT NULL,FOREIGN KEY(assignment_id) REFERENCES fm_service_assignments(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_service_audit(id BIGINT AUTO_INCREMENT PRIMARY KEY,actor_id INT NOT NULL,action VARCHAR(30) NOT NULL,entity_id INT NOT NULL,details_json TEXT NULL,created_at DATETIME NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_service_commands(actor_id INT NOT NULL,request_key CHAR(32) CHARACTER SET ascii NOT NULL,action VARCHAR(30) NOT NULL,input_hash CHAR(64) NOT NULL,result_json TEXT NOT NULL,created_at DATETIME NOT NULL,PRIMARY KEY(actor_id,request_key)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
   "CREATE TABLE IF NOT EXISTS fm_service_payments(id BIGINT AUTO_INCREMENT PRIMARY KEY,main_id INT NOT NULL,actor_id INT NOT NULL,previous_date DATE NOT NULL,next_date DATE NOT NULL,created_at DATETIME NOT NULL,FOREIGN KEY(main_id) REFERENCES fm_service_mains(id),INDEX(main_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
  ] as $sql)$db->query($sql);
  $db->query("INSERT IGNORE INTO fm_service_types(code,name,max_accounts,max_profiles) VALUES('spotify','Spotify',5,1)");
  SpotifySalesMigration::apply($db);
 }
}
