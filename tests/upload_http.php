<?php
// Run with: php tests/upload_http.php. Uses a loopback-only server and temporary files.
require dirname(__DIR__) . '/app/Core/FileUploader.php';
if (PHP_SAPI === 'cli-server') {
    $folder = sys_get_temp_dir() . '/svp-upload-' . bin2hex(random_bytes(8));
    $uploaded = null;
    try {
        $uploaded = FileUploader::uploadPdf('document', $folder);
        header('Content-Type: application/json');
        echo json_encode(['uploaded' => $uploaded, 'exists' => $uploaded && FileUploader::resolve($folder, $uploaded['stored_path']) !== null]);
    } catch (RuntimeException $e) {
        http_response_code(422);
        echo json_encode(['error' => $e->getMessage()]);
    } finally {
        if ($uploaded) FileUploader::discard($folder, $uploaded['stored_path']);
        if (is_dir($folder)) rmdir($folder);
    }
    return;
}
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
if (!$socket) throw new RuntimeException($error);
$address = stream_socket_get_name($socket, false);
fclose($socket);
$log = tempnam(sys_get_temp_dir(), 'svp-log-');
$fixture = tempnam(sys_get_temp_dir(), 'svp-pdf-');
$process = proc_open([PHP_BINARY, '-d', 'upload_tmp_dir=' . sys_get_temp_dir(), '-d', 'upload_max_filesize=12M', '-d', 'post_max_size=14M', '-S', $address, __FILE__], [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, __DIR__);
if (!is_resource($process)) throw new RuntimeException('Unable to start upload test server');
fclose($pipes[0]);
function requestUpload(string $address, string $fixture): array {
    $curl = curl_init('http://' . $address . '/');
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_POST => true, CURLOPT_POSTFIELDS => ['document' => new CURLFile($fixture, 'application/pdf', 'report.pdf')]]);
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    return [$status, json_decode($body ?: '{}', true)];
}
try {
    for ($i = 0; $i < 50; $i++) {
        $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($connection) { fclose($connection); break; }
        usleep(100000);
    }
    file_put_contents($fixture, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n");
    [$status, $result] = requestUpload($address, $fixture);
    if ($status !== 200 || empty($result['exists'])) throw new RuntimeException('Valid PDF upload failed: ' . json_encode([$status, $result]) . '\n' . file_get_contents($log));
    file_put_contents($fixture, 'This is not a PDF despite its extension.');
    [$status] = requestUpload($address, $fixture);
    if ($status !== 422) throw new RuntimeException('Fake PDF was accepted');
    file_put_contents($fixture, '');
    [$status] = requestUpload($address, $fixture);
    if ($status !== 422) throw new RuntimeException('Empty upload was accepted');
    file_put_contents($fixture, "%PDF-1.4\n" . str_repeat('x', 10 * 1024 * 1024));
    [$status] = requestUpload($address, $fixture);
    if ($status !== 422) throw new RuntimeException('Oversized upload was accepted');
    echo "PASS: 4 real HTTP multipart upload checks\n";
} finally {
    proc_terminate($process);
    proc_close($process);
    unlink($fixture);
    unlink($log);
}
