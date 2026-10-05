<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PM_RECORD_PATH', sys_get_temp_dir() . '/svp-pdf-test-' . bin2hex(random_bytes(8)));
foreach (['Controller', 'Session', 'FileUploader', 'SharePointClient'] as $class) require BASE_PATH . '/app/Core/' . $class . '.php';
require BASE_PATH . '/app/Controllers/PmRecordController.php';
$config = ['tenant_id' => 'test.example', 'client_id' => 'app', 'client_secret' => 'secret', 'site_id' => 'site', 'drive_id' => 'drive', 'export_folder_id' => 'folder'];
$calls = [];
$fail = false;
$client = new SharePointClient($config, static function ($method, $url, $headers, $body) use (&$calls, &$fail) {
    $calls[] = compact('method', 'url', 'headers', 'body');
    if ($method === 'POST') return ['status' => 200, 'body' => '{"access_token":"token","expires_in":3600}'];
    return ['status' => $fail ? 403 : 201, 'body' => $fail ? '{}' : '{"id":"uploaded"}'];
});
$checks = 0;
function verifyPdf(bool $condition, string $label): void {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    $checks++;
}
class PdfSendHarness extends PmRecordController {
    public SharePointClient $client;
    protected function sharePointClient(): SharePointClient { return $this->client; }
    public function send(int $id, string $name): void { $this->sendPdfToSharePoint($id, $name); }
}
$name = str_repeat('a', 32) . '.pdf';
$path = UPLOAD_PM_RECORD_PATH . '/' . $name;
mkdir(UPLOAD_PM_RECORD_PATH);
try {
    $pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n";
    file_put_contents($path, $pdf);
    $client->uploadRecordPdf(7, $name);
    $first = end($calls);
    verifyPdf($first['method'] === 'PUT' && $first['body'] === $pdf, 'PDF bytes uploaded');
    verifyPdf(in_array('Content-Type: application/pdf', $first['headers'], true), 'PDF content type');
    verifyPdf(str_contains($first['url'], '/items/folder:/PM-record-7-'), 'configured folder and record identity');
    $client->uploadRecordPdf(7, $name);
    verifyPdf(end($calls)['url'] === $first['url'], 'retry targets same file');
    foreach (['../' . $name, str_repeat('b', 32) . '.pdf'] as $invalid) {
        $before = count($calls);
        try { $client->uploadRecordPdf(7, $invalid); throw new LogicException('Expected rejection'); }
        catch (RuntimeException $e) { verifyPdf(count($calls) === $before, 'invalid local path never sent'); }
    }
    $harness = new PdfSendHarness(); $harness->client = $client;
    $_SESSION = [];
    $harness->send(7, $name);
    verifyPdf(str_contains(Session::flash('success'), 'SharePoint'), 'success notification');
    $fail = true;
    $harness->send(7, $name);
    verifyPdf(str_contains(Session::flash('error'), 'ไม่ต้องบันทึก PM ใหม่'), 'remote failure gives retry instructions');
    verifyPdf(file_get_contents($path) === $pdf, 'remote failure preserves original PDF');
    file_put_contents($path, 'not a PDF');
    try { $client->uploadRecordPdf(7, $name); throw new LogicException('Expected rejection'); }
    catch (RuntimeException $e) { verifyPdf(str_contains($e->getMessage(), 'ไม่ใช่ PDF'), 'non-PDF rejected'); }
    $routes = require BASE_PATH . '/app/Config/routes.php';
    verifyPdf($routes['POST /pm-records/{id}/sharepoint'][2] === ['auth', 'role:admin'], 'retry requires admin and POST');
    echo "PASS: {$checks} PDF SharePoint checks (mock HTTP)\n";
} finally {
    unlink($path);
    rmdir(UPLOAD_PM_RECORD_PATH);
}
