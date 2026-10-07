<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/regression.php';
// View configuration must stay isolated from production .env and credentials.
function env(string $key, $default = null) { return $default; }
$start = $checks;
$config = ['tenant_id' => 'example.onmicrosoft.com', 'client_id' => 'test-app', 'client_secret' => 'private-secret', 'site_id' => 'host,site,web', 'drive_id' => 'drive', 'export_folder_id' => 'exports'];
$calls = [];
$queue = [];
$transport = static function ($method, $url, $headers, $body) use (&$calls, &$queue) {
    $calls[] = compact('method', 'url', 'headers', 'body');
    if (!$queue) throw new RuntimeException('Unexpected network request');
    return array_shift($queue);
};
$ok = static fn($data) => ['status' => 200, 'body' => json_encode($data)];
$token = $ok(['access_token' => 'test-token', 'expires_in' => 3600]);
$client = new SharePointClient([], $transport);
check(count($client->missingSettings()) === 5 && !$client->canExport(), 'missing configuration disables export');
try { $client->exportRecords([]); check(false, 'missing config rejected'); } catch (RuntimeException $e) { check(count($calls) === 0, 'no network when unconfigured'); }
$queue = [$token, $ok(['id' => 'site', 'displayName' => 'Factory']), $ok(['value' => [['id' => 'file', 'name' => 'PM.xlsx']], '@odata.nextLink' => 'https://graph.microsoft.com/v1.0/anything?$skiptoken=next%2Bpage'])];
$client = new SharePointClient($config, $transport);
check($client->site()['displayName'] === 'Factory', 'site lookup');
$page = $client->files('folder/ไทย');
check($page['cursor'] === 'next+page' && count($page['items']) === 1, 'list files and extract pagination');
check(count($calls) === 3, 'token reused within client');
check(str_contains($calls[2]['url'], 'folder%2F%E0%B9%84%E0%B8%97%E0%B8%A2'), 'folder IDs encoded as one segment');
parse_str($calls[0]['body'], $form);
check($form['client_secret'] === 'private-secret' && $form['grant_type'] === 'client_credentials' && $form['scope'] === 'https://graph.microsoft.com/.default', 'OAuth request uses client credentials');
check(!str_contains($calls[1]['url'], 'private-secret'), 'secret not in Graph URL');
$queue = [$ok(['value' => []])];
$client->files('', 'https://evil.example/?token=secret');
check(str_starts_with(end($calls)['url'], 'https://graph.microsoft.com/v1.0/sites/'), 'cursor cannot choose remote host');
try { $client->files(str_repeat('x', 257)); check(false, 'long folder rejected'); } catch (InvalidArgumentException $e) { check(true, 'long folder rejected'); }
$queue = [$ok(['value' => [], '@odata.nextLink' => 'https://evil.example/'])];
try { $client->files(); check(false, 'bad paging rejected'); } catch (RuntimeException $e) { check(true, 'bad paging reported rather than dropping results'); }
foreach ([400, 401, 403, 404, 429, 500, 302] as $status) {
    $queue = [['status' => $status, 'body' => 'private-secret test-token']];
    try { $client->site(); check(false, 'HTTP error rejected'); }
    catch (RuntimeException $e) { check(str_contains($e->getMessage(), (string) $status) && !str_contains($e->getMessage(), 'private-secret') && !str_contains($e->getMessage(), 'test-token'), 'sanitized upstream failure ' . $status); }
}
$queue = [['status' => 200, 'body' => '<html>bad gateway</html>']];
try { $client->site(); check(false, 'invalid JSON rejected'); } catch (RuntimeException $e) { check(true, 'invalid JSON rejected'); }
$records = [['pm_record_id' => 1, 'performed_date' => '2026-10-05', 'svp_device_name' => 'เครื่องทดสอบ', 'pm_title' => '=HYPERLINK("test")', 'first_name' => 'ทดสอบ', 'last_name' => 'ระบบ', 'result_status' => 'completed', 'notes' => "  +SUM(1,2)\nnext"]];
$csv = SharePointClient::recordsCsv($records);
check(str_starts_with($csv, "\xEF\xBB\xBF") && str_contains($csv, 'เครื่องทดสอบ'), 'Thai UTF-8 BOM for Excel');
check(str_contains($csv, "'=HYPERLINK") && str_contains($csv, "'  +SUM"), 'CSV formula injection neutralized');
$queue = [$ok(['id' => 'new-file', 'name' => 'export.csv'])];
check($client->exportRecords($records)['id'] === 'new-file', 'export returns uploaded file');
$upload = end($calls);
check($upload['method'] === 'PUT' && str_contains($upload['url'], '/items/exports:/PM-records-') && str_ends_with($upload['url'], '.csv:/content'), 'export targets configured folder and unique CSV');
check($upload['body'] === $csv, 'CSV sent as file content');
$queue = [$ok(['access_token' => 'expired', 'expires_in' => 1]), $ok(['id' => 'site']), $token, $ok(['id' => 'site'])];
$client = new SharePointClient($config, $transport);
$client->site(); $client->site();
check(!$queue, 'expired token reacquired');
$routes = require BASE_PATH . '/app/Config/routes.php';
foreach (['GET /sharepoint', 'POST /sharepoint/export'] as $route) check($routes[$route][2] === ['auth', 'role:admin'], 'SharePoint routes require active admin');
$html = render('sharepoint/index', ['missing' => [], 'site' => ['displayName' => '<script>bad</script>'], 'error' => null, 'folder' => '', 'canExport' => true, 'page' => ['items' => [['name' => '<img src=x>', 'webUrl' => 'javascript:alert(1)']], 'cursor' => '']]);
check(!str_contains($html, '<script>') && !str_contains($html, '<img src=x>') && !str_contains($html, 'javascript:'), 'upstream names escaped and unsafe file URL omitted');
check(str_contains($html, 'name="_csrf"'), 'export form contains CSRF');
echo 'PASS: ' . ($checks - $start) . " SharePoint checks (mock HTTP; no tenant or database writes)\n";
