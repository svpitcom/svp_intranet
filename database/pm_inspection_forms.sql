CREATE TABLE IF NOT EXISTS pm_inspection_form (
    pm_record_id INT UNSIGNED NOT NULL PRIMARY KEY,
    payload LONGTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pm_inspection_record FOREIGN KEY (pm_record_id)
        REFERENCES pm_record(pm_record_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
