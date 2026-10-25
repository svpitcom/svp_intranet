CREATE TABLE IF NOT EXISTS machine_schema (
    version INT NOT NULL PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO machine_schema(version) VALUES(1);

CREATE TABLE IF NOT EXISTS machines (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(200) NOT NULL,
    machine_type VARCHAR(120) NOT NULL DEFAULT '',
    brand VARCHAR(120) NOT NULL DEFAULT '',
    model VARCHAR(120) NOT NULL DEFAULT '',
    serial_number VARCHAR(150) NOT NULL DEFAULT '',
    location VARCHAR(200) NOT NULL DEFAULT '',
    department_id INT UNSIGNED NULL,
    department_name VARCHAR(200) NOT NULL DEFAULT '',
    owner_id INT UNSIGNED NULL,
    owner_name VARCHAR(200) NOT NULL DEFAULT '',
    is_active TINYINT NOT NULL DEFAULT 1,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_machine_active(is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS machine_pm_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    machine_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    checklist TEXT NOT NULL,
    frequency_days INT UNSIGNED NOT NULL,
    responsible_id INT UNSIGNED NULL,
    responsible_name VARCHAR(200) NOT NULL DEFAULT '',
    next_pm_date DATE NOT NULL,
    last_pm_date DATE NULL,
    is_active TINYINT NOT NULL DEFAULT 1,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_machine_plan FOREIGN KEY(machine_id) REFERENCES machines(id) ON DELETE RESTRICT,
    INDEX idx_machine_due(is_active,next_pm_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS machine_pm_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT UNSIGNED NOT NULL,
    machine_id INT UNSIGNED NOT NULL,
    machine_code VARCHAR(80) NOT NULL,
    machine_name VARCHAR(200) NOT NULL,
    performed_date DATE NOT NULL,
    performed_by INT UNSIGNED NOT NULL,
    performed_name VARCHAR(200) NOT NULL,
    result_status VARCHAR(20) NOT NULL,
    payload LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_machine_record_plan FOREIGN KEY(schedule_id) REFERENCES machine_pm_schedules(id) ON DELETE RESTRICT,
    CONSTRAINT fk_machine_record_machine FOREIGN KEY(machine_id) REFERENCES machines(id) ON DELETE RESTRICT,
    INDEX idx_machine_record_date(performed_date,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS machine_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(20) NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED NOT NULL,
    actor_name VARCHAR(200) NOT NULL,
    payload LONGTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_machine_event(entity_type,entity_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
