
-- =====================================================
-- SVP_INTRANET : PM MODULE
-- MySQL 8.0+
-- Exclude: device_type, device_it
-- Total: 10 Tables
-- =====================================================

SET NAMES utf8mb4;

-- =====================================================
-- 1. PM TEMPLATE GROUPS
-- Mapping Device Type -> PM Template
-- =====================================================

CREATE TABLE `pm_it_template_groups` (
    `pm_it_template_group_id` INT NOT NULL AUTO_INCREMENT,
    `eq_device_type_id` INT NOT NULL,

    `template_code` VARCHAR(50) NOT NULL,
    `template_name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,

    `status` ENUM('active','inactive')
        NOT NULL DEFAULT 'active',

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`pm_it_template_group_id`),

    UNIQUE KEY `uq_pm_template_code` (`template_code`),
    UNIQUE KEY `uq_pm_template_device_type` (`eq_device_type_id`),

    CONSTRAINT `fk_pm_template_group_device_type`
        FOREIGN KEY (`eq_device_type_id`)
        REFERENCES `device_type` (`eq_device_type_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 2. PM TEMPLATES
-- Store Template Revisions
-- =====================================================

CREATE TABLE `pm_it_templates` (
    `pm_it_templates_id` INT NOT NULL AUTO_INCREMENT,
    `pm_it_template_group_id` INT NOT NULL,

    `revision` VARCHAR(20) NOT NULL,
    `frequency_days` INT NOT NULL DEFAULT 180,

    `effective_date` DATE DEFAULT NULL,
    `expiry_date` DATE DEFAULT NULL,

    `change_note` TEXT DEFAULT NULL,

    `status` ENUM('draft','published','retired')
        NOT NULL DEFAULT 'draft',

    `created_by` BIGINT DEFAULT NULL,
    `published_at` DATETIME DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`pm_it_templates_id`),

    UNIQUE KEY `uq_pm_template_revision`
        (`pm_it_template_group_id`, `revision`),

    KEY `idx_pm_template_status`
        (`pm_it_template_group_id`, `status`),

    CONSTRAINT `fk_pm_templates_group`
        FOREIGN KEY (`pm_it_template_group_id`)
        REFERENCES `pm_it_template_groups` (`pm_it_template_group_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `chk_pm_template_frequency`
        CHECK (`frequency_days` > 0),

    CONSTRAINT `chk_pm_template_dates`
        CHECK (
            `expiry_date` IS NULL
            OR `effective_date` IS NULL
            OR `expiry_date` >= `effective_date`
        )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 3. PM TEMPLATE ITEMS
-- Dynamic Inspection Checklist
-- =====================================================

CREATE TABLE `pm_it_templates_items` (
    `pm_it_templates_items_id` INT NOT NULL AUTO_INCREMENT,
    `pm_it_templates_id` INT NOT NULL,

    `sequence` INT NOT NULL,
    `section` VARCHAR(100) DEFAULT NULL,
    `item_code` VARCHAR(50) DEFAULT NULL,

    `pm_it_name` VARCHAR(255) NOT NULL,
    `inspection_method` TEXT DEFAULT NULL,
    `standard` TEXT DEFAULT NULL,

    `item_type` ENUM(
        'checkbox',
        'text',
        'number',
        'select',
        'pass_fail'
    ) NOT NULL DEFAULT 'pass_fail',

    `options_json` JSON DEFAULT NULL,

    `is_required` BOOLEAN NOT NULL DEFAULT TRUE,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`pm_it_templates_items_id`),

    UNIQUE KEY `uq_pm_template_item_sequence`
        (`pm_it_templates_id`, `sequence`),

    KEY `idx_pm_template_item_active`
        (`pm_it_templates_id`, `is_active`),

    CONSTRAINT `fk_pm_template_items_template`
        FOREIGN KEY (`pm_it_templates_id`)
        REFERENCES `pm_it_templates` (`pm_it_templates_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `chk_pm_item_sequence`
        CHECK (`sequence` > 0)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 4. PM SCHEDULE BATCHES
-- Create PM Plan by Device Type or Device ID
-- =====================================================

CREATE TABLE `pm_schedule_batches` (
    `pm_schedule_batch_id` INT NOT NULL AUTO_INCREMENT,

    `batch_no` VARCHAR(50) NOT NULL,

    `target_scope` ENUM('device_type','device')
        NOT NULL,

    `eq_device_type_id` INT DEFAULT NULL,
    `eq_device_id` INT DEFAULT NULL,

    `pm_it_templates_id` INT NOT NULL,

    `scheduled_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,

    `created_by` BIGINT DEFAULT NULL,

    `status` ENUM('draft','scheduled','cancelled')
        NOT NULL DEFAULT 'draft',

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`pm_schedule_batch_id`),

    UNIQUE KEY `uq_pm_batch_no` (`batch_no`),

    KEY `idx_pm_batch_device_type` (`eq_device_type_id`),
    KEY `idx_pm_batch_device` (`eq_device_id`),
    KEY `idx_pm_batch_date` (`scheduled_date`, `status`),

    CONSTRAINT `fk_pm_batch_device_type`
        FOREIGN KEY (`eq_device_type_id`)
        REFERENCES `device_type` (`eq_device_type_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_pm_batch_device`
        FOREIGN KEY (`eq_device_id`)
        REFERENCES `device_it` (`eq_device_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_pm_batch_template`
        FOREIGN KEY (`pm_it_templates_id`)
        REFERENCES `pm_it_templates` (`pm_it_templates_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `chk_pm_batch_target`
        CHECK (
            (
                `target_scope` = 'device_type'
                AND `eq_device_type_id` IS NOT NULL
                AND `eq_device_id` IS NULL
            )
            OR
            (
                `target_scope` = 'device'
                AND `eq_device_id` IS NOT NULL
                AND `eq_device_type_id` IS NULL
            )
        ),

    CONSTRAINT `chk_pm_batch_dates`
        CHECK (`due_date` >= `scheduled_date`)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 5. PM SCHEDULE IT
-- Individual Device Schedule
-- =====================================================

CREATE TABLE `pm_schedule_it` (
    `pm_schedule_it_id` INT NOT NULL AUTO_INCREMENT,

    `pm_schedule_batch_id` INT NOT NULL,
    `eq_device_id` INT NOT NULL,

    `assigned_to` BIGINT DEFAULT NULL,

    `scheduled_date` DATE NOT NULL,
    `due_date` DATE NOT NULL,

    `status` ENUM(
        'pending',
        'in_progress',
        'completed',
        'cancelled'
    ) NOT NULL DEFAULT 'pending',

    `started_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`pm_schedule_it_id`),

    UNIQUE KEY `uq_pm_batch_device`
        (`pm_schedule_batch_id`, `eq_device_id`),

    KEY `idx_pm_schedule_device` (`eq_device_id`),
    KEY `idx_pm_schedule_status_date` (`status`, `due_date`),

    CONSTRAINT `fk_pm_schedule_batch`
        FOREIGN KEY (`pm_schedule_batch_id`)
        REFERENCES `pm_schedule_batches` (`pm_schedule_batch_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_pm_schedule_device`
        FOREIGN KEY (`eq_device_id`)
        REFERENCES `device_it` (`eq_device_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `chk_pm_schedule_dates`
        CHECK (`due_date` >= `scheduled_date`)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 6. PM RECORD IT
-- Main Inspection Record
-- =====================================================

CREATE TABLE `pm_record_it` (
    `pm_record_it_id` INT NOT NULL AUTO_INCREMENT,

    `record_no` VARCHAR(50) NOT NULL,

    `pm_schedule_it_id` INT DEFAULT NULL,
    `eq_device_id` INT NOT NULL,
    `pm_it_templates_id` INT NOT NULL,

    -- Device Snapshot
    `device_code_snapshot` VARCHAR(100) DEFAULT NULL,
    `device_name_snapshot` VARCHAR(255) DEFAULT NULL,
    `device_type_snapshot` VARCHAR(150) DEFAULT NULL,

    -- Template Snapshot
    `template_revision_snapshot` VARCHAR(20) NOT NULL,

    `inspector_id` BIGINT DEFAULT NULL,

    `inspection_date` DATE NOT NULL,
    `start_time` TIME DEFAULT NULL,
    `end_time` TIME DEFAULT NULL,

    `status` ENUM(
        'draft',
        'submitted',
        'approved',
        'rejected',
        'cancelled'
    ) NOT NULL DEFAULT 'draft',

    `approved_by` BIGINT DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`pm_record_it_id`),

    UNIQUE KEY `uq_pm_record_no` (`record_no`),

    UNIQUE KEY `uq_pm_record_schedule` (`pm_schedule_it_id`),

    KEY `idx_pm_record_device_date`
        (`eq_device_id`, `inspection_date`),

    KEY `idx_pm_record_status` (`status`),

    CONSTRAINT `fk_pm_record_schedule`
        FOREIGN KEY (`pm_schedule_it_id`)
        REFERENCES `pm_schedule_it` (`pm_schedule_it_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_pm_record_device`
        FOREIGN KEY (`eq_device_id`)
        REFERENCES `device_it` (`eq_device_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_pm_record_template`
        FOREIGN KEY (`pm_it_templates_id`)
        REFERENCES `pm_it_templates` (`pm_it_templates_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 7. PM RECORD ITEMS
-- Inspection Results + Checklist Snapshot
-- =====================================================

CREATE TABLE `pm_record_items` (
    `pm_record_items_id` INT NOT NULL AUTO_INCREMENT,

    `pm_record_it_id` INT NOT NULL,

    `pm_it_templates_items_id` INT DEFAULT NULL,

    -- Checklist Snapshot
    `sequence_snapshot` INT NOT NULL,
    `topic_snapshot` VARCHAR(255) NOT NULL,
    `section_snapshot` VARCHAR(100) DEFAULT NULL,
    `method_snapshot` TEXT DEFAULT NULL,
    `standard_snapshot` TEXT DEFAULT NULL,
    `item_type_snapshot` VARCHAR(30) DEFAULT NULL,

    -- Inspection Result
    `result` ENUM(
        'normal',
        'abnormal',
        'na'
    ) DEFAULT NULL,

    `value` TEXT DEFAULT NULL,
    `detail` TEXT DEFAULT NULL,
    `remark` TEXT DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`pm_record_items_id`),

    UNIQUE KEY `uq_pm_record_item_sequence`
        (`pm_record_it_id`, `sequence_snapshot`),

    KEY `idx_pm_record_item_template`
        (`pm_it_templates_items_id`),

    CONSTRAINT `fk_pm_record_items_record`
        FOREIGN KEY (`pm_record_it_id`)
        REFERENCES `pm_record_it` (`pm_record_it_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_pm_record_items_template_item`
        FOREIGN KEY (`pm_it_templates_items_id`)
        REFERENCES `pm_it_templates_items` (`pm_it_templates_items_id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 8. PM OPERATIONS
-- Maintenance / Corrective Actions
-- =====================================================

CREATE TABLE `pm_operations` (
    `pm_operations_id` INT NOT NULL AUTO_INCREMENT,

    `pm_record_it_id` INT NOT NULL,
    `pm_record_items_id` INT DEFAULT NULL,

    `pm_it_name` VARCHAR(255) NOT NULL,
    `pm_it_detail` TEXT DEFAULT NULL,

    `status` ENUM(
        'pending',
        'in_progress',
        'completed',
        'incomplete'
    ) NOT NULL DEFAULT 'pending',

    `result` ENUM(
        'normal',
        'abnormal'
    ) DEFAULT NULL,

    `performed_by` BIGINT DEFAULT NULL,
    `performed_at` DATETIME DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`pm_operations_id`),

    KEY `idx_pm_operation_record` (`pm_record_it_id`),
    KEY `idx_pm_operation_item` (`pm_record_items_id`),

    CONSTRAINT `fk_pm_operations_record`
        FOREIGN KEY (`pm_record_it_id`)
        REFERENCES `pm_record_it` (`pm_record_it_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT `fk_pm_operations_record_item`
        FOREIGN KEY (`pm_record_items_id`)
        REFERENCES `pm_record_items` (`pm_record_items_id`)
        ON UPDATE CASCADE
        ON DELETE SET NULL

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 9. PM ATTACHMENTS
-- Images / Documents
-- =====================================================

CREATE TABLE `pm_attachments` (
    `pm_attachment_id` INT NOT NULL AUTO_INCREMENT,

    `pm_record_it_id` INT NOT NULL,

    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `file_size` BIGINT DEFAULT NULL,

    `uploaded_by` BIGINT DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`pm_attachment_id`),

    KEY `idx_pm_attachment_record` (`pm_record_it_id`),

    CONSTRAINT `fk_pm_attachment_record`
        FOREIGN KEY (`pm_record_it_id`)
        REFERENCES `pm_record_it` (`pm_record_it_id`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- 10. PM AUDIT LOGS
-- Track All PM Changes
-- =====================================================

CREATE TABLE `pm_audit_logs` (
    `pm_audit_log_id` BIGINT NOT NULL AUTO_INCREMENT,

    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` BIGINT NOT NULL,

    `action` VARCHAR(30) NOT NULL,

    `old_values` JSON DEFAULT NULL,
    `new_values` JSON DEFAULT NULL,

    `performed_by` BIGINT DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`pm_audit_log_id`),

    KEY `idx_pm_audit_entity`
        (`entity_type`, `entity_id`),

    KEY `idx_pm_audit_performed_by`
        (`performed_by`),

    KEY `idx_pm_audit_created_at` (`created_at`)

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =====================================================
-- END OF PM MODULE
-- =====================================================
