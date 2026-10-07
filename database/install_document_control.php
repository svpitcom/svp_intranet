<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Config/config.php';
require BASE_PATH . '/app/Config/database.php';
try {
    $db = Database::connect();
    $db->exec(file_get_contents(__DIR__ . '/document_control.sql'));
    echo "Document Control tables ready. Existing data preserved.\n";
} catch (Throwable $e) { fwrite(STDERR, "Document Control installation failed; check database access.\n"); exit(1); }
