<?php

/**
 * จัดการอัปโหลดไฟล์แบบปลอดภัย (ตรวจ mime type จริง ไม่เชื่อแค่นามสกุลไฟล์)
 */
class FileUploader
{
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB

    /**
     * @return array{original_name:string, stored_path:string}|null คืนค่า null ถ้าไม่มีไฟล์แนบมา (ไม่ error)
     * @throws RuntimeException ถ้าไฟล์ที่แนบมาผิดเงื่อนไข
     */
    public static function uploadPdf(string $inputName, string $destinationFolder): ?array
    {
        if (!isset($_FILES[$inputName])) {
            return null; // ผู้ใช้ไม่ได้แนบไฟล์มา ไม่ใช่ error
        }

        $file = $_FILES[$inputName];
        if (!is_array($file) || !isset($file['error']) || !is_int($file['error'])) {
            throw new RuntimeException('ข้อมูลไฟล์อัปโหลดไม่ถูกต้อง');
        }
        if ($file['error'] === UPLOAD_ERR_NO_FILE) return null;

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('อัปโหลดไฟล์ไม่สำเร็จ (รหัส error: ' . $file['error'] . ')');
        }

        if (!isset($file['tmp_name'], $file['name']) || !is_string($file['tmp_name']) || !is_string($file['name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('ข้อมูลไฟล์อัปโหลดไม่ถูกต้อง');
        }
        $size = filesize($file['tmp_name']);
        if ($size === false || $size === 0) {
            throw new RuntimeException('ไฟล์ว่างหรือไม่สามารถอ่านได้');
        }
        if ($size > self::MAX_SIZE_BYTES) {
            throw new RuntimeException('ไฟล์มีขนาดใหญ่เกิน 10 MB');
        }

        // ตรวจ mime type จริงจากเนื้อไฟล์ (ไม่เชื่อแค่นามสกุล .pdf ที่ผู้ใช้ตั้งชื่อมาเอง)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mimeType !== 'application/pdf') {
            throw new RuntimeException('รองรับเฉพาะไฟล์ PDF เท่านั้น');
        }

        if (!is_dir($destinationFolder) && !mkdir($destinationFolder, 0755, true) && !is_dir($destinationFolder)) {
            throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์เก็บไฟล์ได้');
        }

        // สุ่มชื่อไฟล์ใหม่ทุกครั้ง กันชื่อไฟล์ชนกัน และกันคนเดารูปแบบชื่อไฟล์คนอื่น
        $storedName = bin2hex(random_bytes(16)) . '.pdf';
        $destinationPath = rtrim($destinationFolder, '/') . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
            throw new RuntimeException('ไม่สามารถบันทึกไฟล์ลงเซิร์ฟเวอร์ได้');
        }

        return [
            'original_name' => mb_substr(basename(str_replace('\\', '/', $file['name'])), 0, 255),
            'stored_path'   => $storedName, // เก็บแค่ชื่อไฟล์ ไม่เก็บ path เต็ม (กัน path traversal ตอนดึงกลับมาใช้)
        ];
    }

    public static function resolve(string $folder, ?string $name): ?string
    {
        if (!$name || !preg_match('/\A[a-f0-9]{32}\.pdf\z/', $name)) return null;
        $root = realpath($folder);
        $path = realpath($folder . '/' . $name);
        return $root !== false && $path !== false && dirname($path) === $root && is_file($path) ? $path : null;
    }

    public static function discard(string $folder, ?string $name): void
    {
        $path = self::resolve($folder, $name);
        if ($path !== null && !unlink($path)) error_log('Unable to remove unused attachment: ' . $name);
    }
}
