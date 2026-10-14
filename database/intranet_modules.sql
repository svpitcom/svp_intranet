CREATE TABLE IF NOT EXISTS work_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kind VARCHAR(16) NOT NULL,
 title VARCHAR(200) NOT NULL,
 description TEXT NOT NULL,
 category VARCHAR(40) NOT NULL,
 priority VARCHAR(16) NOT NULL DEFAULT 'normal',
 device_id INT UNSIGNED NULL,
 location VARCHAR(200) NOT NULL DEFAULT '',
 requester_id INT UNSIGNED NOT NULL,
 assignee_id INT UNSIGNED NULL,
 approver_id INT UNSIGNED NULL,
 status VARCHAR(24) NOT NULL,
 due_date DATE NULL,
 resolution TEXT NULL,
 attachment_path VARCHAR(100) NULL,
 attachment_name VARCHAR(255) NULL,
 version INT UNSIGNED NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX work_kind_status(kind,status), INDEX work_requester(requester_id),
 INDEX work_assignee(assignee_id), INDEX work_approver(approver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS work_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 work_id BIGINT UNSIGNED NOT NULL,
 actor_id INT UNSIGNED NOT NULL,
 action VARCHAR(32) NOT NULL,
 body TEXT NOT NULL,
 created_at DATETIME NOT NULL,
 INDEX event_work(work_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS announcements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(200) NOT NULL,
 body TEXT NOT NULL,
 department_id INT UNSIGNED NULL,
 author_id INT UNSIGNED NOT NULL,
 status VARCHAR(16) NOT NULL DEFAULT 'draft',
 publish_date DATE NOT NULL,
 expires_date DATE NULL,
 pinned TINYINT NOT NULL DEFAULT 0,
 version INT UNSIGNED NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX announcement_visibility(status,publish_date,expires_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS announcement_reads (
 announcement_id BIGINT UNSIGNED NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 read_at DATETIME NOT NULL,
 PRIMARY KEY(announcement_id,user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS portal_notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 work_id BIGINT UNSIGNED NOT NULL,
 message VARCHAR(255) NOT NULL,
 read_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 INDEX notification_user(user_id,read_at,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
