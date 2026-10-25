CREATE TABLE IF NOT EXISTS kpi_forms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    year SMALLINT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    payload LONGTEXT NOT NULL,
    version INT UNSIGNED NOT NULL DEFAULT 1,
    updated_by INT UNSIGNED NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX form_scope(year, department_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS kpi_form_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id BIGINT UNSIGNED NOT NULL,
    payload LONGTEXT NOT NULL,
    version INT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY form_revision(form_id, version)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;