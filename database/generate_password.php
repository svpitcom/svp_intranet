<?php

/**
 * ใช้สร้าง bcrypt hash สำหรับ insert ข้อมูล users
 * วิธีใช้: php database/generate_password.php "password123"
 */
if ($argc < 2) {
    echo "วิธีใช้: php generate_password.php \"your-password\"\n";
    exit(1);
}

echo password_hash($argv[1], PASSWORD_BCRYPT) . PHP_EOL;
