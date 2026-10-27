CREATE TABLE IF NOT EXISTS work_machine_forms (
 work_id BIGINT UNSIGNED PRIMARY KEY,
 request_data TEXT NOT NULL,
 repair_data TEXT NULL,
 acceptance_data TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
