<?php
// CLI-only, read devices or upload the one explicitly named demo workbook.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Config/config.php';
require BASE_PATH . '/app/Config/database.php';
require BASE_PATH . '/app/Core/Model.php';
require BASE_PATH . '/app/Models/Device.php';
try {
    if (($argv[1] ?? '') === 'data') {
        echo json_encode((new Device())->allWithDevice(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
    if (($argv[1] ?? '') !== 'upload') throw new RuntimeException('Use data or upload');
    $file = 'C:/Users/thaw0524/.codex/visualizations/2026/09/30/01a0f15c-07ae-78b0-8d8e-0886f7930459/outputs/excel-demo/Master-List-Devices-DEMO.xlsx';
    $config = require BASE_PATH . '/app/Config/sharepoint.php';
    function demoRequest(string $url, array $headers, string $method, string $body): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CUSTOMREQUEST=>$method, CURLOPT_HTTPHEADER=>$headers, CURLOPT_POSTFIELDS=>$body, CURLOPT_TIMEOUT=>60, CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2]);
        $result = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($result === false || $status < 200 || $status >= 300) throw new RuntimeException('Microsoft request failed HTTP ' . $status);
        return json_decode($result, true, 512, JSON_THROW_ON_ERROR);
    }
    $token = demoRequest('https://login.microsoftonline.com/' . rawurlencode($config['tenant_id']) . '/oauth2/v2.0/token', ['Content-Type: application/x-www-form-urlencoded'], 'POST', http_build_query(['client_id'=>$config['client_id'],'client_secret'=>$config['client_secret'],'grant_type'=>'client_credentials','scope'=>'https://graph.microsoft.com/.default']));
    $name = 'Master-List-Devices-DEMO-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.xlsx';
    $body = file_get_contents($file);
    if ($body === false) throw new RuntimeException('Demo file missing');
    $result = demoRequest('https://graph.microsoft.com/v1.0/drives/' . rawurlencode($config['drive_id']) . '/items/' . rawurlencode($config['export_folder_id']) . ':/' . rawurlencode($name) . ':/content', ['Authorization: Bearer ' . $token['access_token'], 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], 'PUT', $body);
    if (empty($result['id'])) throw new RuntimeException('Upload not confirmed');
    echo json_encode(['name'=>$result['name'],'webUrl'=>$result['webUrl'],'size'=>$result['size']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) { fwrite(STDERR, "Demo operation failed; check database or Microsoft connectivity.\n"); exit(1); }
