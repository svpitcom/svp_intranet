CREATE TABLE IF NOT EXISTS backup_checklists (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 month CHAR(7) NOT NULL,
 location VARCHAR(120) NOT NULL,
 server_name VARCHAR(120) NOT NULL,
 payload LONGTEXT NOT NULL,
 version INT UNSIGNED NOT NULL DEFAULT 1,
 updated_by INT UNSIGNED NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY backup_month_server(month,location,server_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS backup_checklist_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 form_id BIGINT UNSIGNED NOT NULL,
 payload LONGTEXT NOT NULL,
 version INT UNSIGNED NOT NULL,
 actor_id INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 UNIQUE KEY backup_form_version(form_id,version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
