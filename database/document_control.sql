CREATE TABLE IF NOT EXISTS controlled_documents (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(80) NOT NULL UNIQUE,
 title VARCHAR(255) NOT NULL,
 revision VARCHAR(30) NOT NULL,
 department_id INT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'draft',
 effective_date DATE NULL,
 review_date DATE NULL,
 file_item_id VARCHAR(255) NOT NULL DEFAULT '',
 file_name VARCHAR(255) NOT NULL DEFAULT '',
 file_url TEXT NOT NULL,
 notes TEXT NOT NULL,
 version INT NOT NULL DEFAULT 1,
 updated_by INT NOT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX document_status (status),
 INDEX document_department (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS controlled_document_history (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 document_id INT UNSIGNED NOT NULL,
 snapshot LONGTEXT NOT NULL,
 changed_by INT NOT NULL,
 changed_at DATETIME NOT NULL,
 INDEX history_document (document_id),
 CONSTRAINT document_history_parent FOREIGN KEY (document_id) REFERENCES controlled_documents(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
