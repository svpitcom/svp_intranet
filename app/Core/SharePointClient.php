<?php
/** Server-side Microsoft Graph client. Credentials and tokens never reach the browser. */
class SharePointClient
{
    private const GRAPH = 'https://graph.microsoft.com/v1.0';
    public const DEVICE_DEMO_NAME = 'Master-List-Devices-DEMO-20261005-073010-1e030d.xlsx';
    public const USER_DEMO_NAME = 'Master-List-Users-DEMO-20261006.xlsx';
    private array $config;
    private $transport;
    private ?string $token = null;
    private int $expires = 0;
    private bool $departmentTarget = false;

    /** Select only server-configured destinations, never a submitted arbitrary folder ID. */
    public function forDepartment(string $department): self
    {
        if ($department === '') return $this;
        $id = trim((string)($this->config['department_folders'][$department] ?? ''));
        if ($id === '' || preg_match('/[<>\r\n]/', $id)) throw new RuntimeException('ยังไม่ได้ตั้งค่าโฟลเดอร์แผนกที่เลือก');
        $folder = $this->graph('GET', $this->drivePath() . '/items/' . rawurlencode($id) . '?$select=id,name,folder,parentReference');
        if (($folder['id'] ?? '') !== $id || !isset($folder['folder']) || ($folder['parentReference']['driveId'] ?? '') !== $this->config['drive_id']) throw new RuntimeException('ปลายทางแผนกไม่ใช่โฟลเดอร์ใน Document Library ที่ตั้งค่า');
        $client = clone $this;
        $client->config['export_folder_id'] = $id;
        $client->departmentTarget = true;
        return $client;
    }

    public function __construct(?array $config = null, ?callable $transport = null)
    {
        $this->config = $config ?? require BASE_PATH . '/app/Config/sharepoint.php';
        $this->transport = $transport ?? [self::class, 'curlRequest'];
    }

    public function missingSettings(): array
    {
        $missing = [];
        foreach (['tenant_id', 'client_id', 'client_secret', 'site_id', 'drive_id'] as $key) {
            if (trim($this->config[$key] ?? '') === '') $missing[] = 'SHAREPOINT_' . strtoupper($key);
        }
        return $missing;
    }

    public function canExport(): bool
    {
        return !$this->missingSettings() && trim($this->config['export_folder_id'] ?? '') !== '';
    }

    public function site(): array
    {
        return $this->graph('GET', '/sites/' . rawurlencode($this->config['site_id']) . '?$select=id,displayName,webUrl');
    }

    /** Resolve only items inside configured DCC, including nested folders. */
    public function dccItem(string $id = ''): array
    {
        $root=trim((string)($this->config['department_folders']['DCC']??''));
        if ($root==='') throw new RuntimeException('ยังไม่ได้ตั้งค่าโฟลเดอร์ DCC');
        $id=$id===''?$root:$id;
        if (strlen($id)>255) throw new RuntimeException('รหัสไฟล์ไม่ถูกต้อง');
        $read=fn($itemId)=>$this->graph('GET',$this->drivePath().'/items/'.rawurlencode($itemId).'?$select=id,name,webUrl,folder,file,parentReference');
        $item=$read($id); $node=$item; $visited=[];
        for ($depth=0;$depth<32;$depth++) {
            if (($node['parentReference']['driveId']??'')!==$this->config['drive_id']) break;
            if (($node['id']??'')===$root && isset($node['folder'])) return $item;
            $parent=$node['parentReference']['id']??'';
            if ($parent==='' || isset($visited[$parent])) break;
            $visited[$parent]=true; $node=$read($parent);
        }
        throw new RuntimeException('ไฟล์หรือโฟลเดอร์นี้ไม่ได้อยู่ภายใน DCC');
    }

    public function dccFiles(string $folder = '', string $cursor = ''): array
    {
        $item=$this->dccItem($folder);
        if (!isset($item['folder'])) throw new RuntimeException('ปลายทางไม่ใช่โฟลเดอร์');
        return $this->files($item['id'],$cursor)+['folder'=>$item];
    }

    /** Only the configured site's document library is accessible. */
    private function drivePath(): string
    {
        return '/sites/' . rawurlencode($this->config['site_id']) . '/drives/' . rawurlencode($this->config['drive_id']);
    }

    public function files(string $folder = '', string $cursor = ''): array
    {
        if (strlen($folder) > 256 || strlen($cursor) > 8192) throw new InvalidArgumentException('พารามิเตอร์โฟลเดอร์ไม่ถูกต้อง');
        $path = $this->drivePath() . ($folder === '' ? '/root' : '/items/' . rawurlencode($folder)) . '/children';
        $query = ['$select' => 'id,name,webUrl,size,lastModifiedDateTime,folder,file', '$top' => 50];
        if ($cursor !== '') $query['$skiptoken'] = $cursor;
        $data = $this->graph('GET', $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        if (!isset($data['value']) || !is_array($data['value'])) throw new RuntimeException('SharePoint ส่งรูปแบบรายการไฟล์ไม่ถูกต้อง');
        $next = '';
        if (!empty($data['@odata.nextLink'])) {
            // Extract only the paging token; never fetch an upstream or user-supplied URL.
            parse_str(parse_url($data['@odata.nextLink'], PHP_URL_QUERY) ?? '', $params);
            $next = $params['$skiptoken'] ?? $params['$skipToken'] ?? '';
            if (!is_string($next) || $next === '' || strlen($next) > 8192) throw new RuntimeException('ไม่สามารถอ่านหน้าถัดไปจาก SharePoint ได้');
        }
        return ['items' => $data['value'], 'cursor' => $next];
    }

    public function exportRecords(array $records): array
    {
        if (!$this->canExport()) throw new RuntimeException('กรุณาตั้งค่า SHAREPOINT_EXPORT_FOLDER_ID และข้อมูลการเชื่อมต่อก่อนส่งออก');
        $csv = self::recordsCsv($records);
        if (strlen($csv) > 10 * 1024 * 1024) throw new RuntimeException('รายงานเกินขนาด 10 MB ที่ระบบรองรับ');
        $filename = 'PM-records-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.csv';
        return $this->graph('PUT', $this->drivePath() . '/items/' . rawurlencode($this->config['export_folder_id']) . ':/' . rawurlencode($filename) . ':/content', $csv, 'text/csv; charset=utf-8');
    }

    public function syncDeviceDemo(array $devices, ?string $expectedETag = null): array
    {
        if (!$this->canExport()) throw new RuntimeException('กรุณาตั้งค่าการเชื่อมต่อ SharePoint ก่อน');
        if ($this->departmentTarget && $expectedETag === null) {
            $result = $this->graph('PUT', $this->drivePath() . '/items/' . rawurlencode($this->config['export_folder_id']) . ':/' . rawurlencode(self::DEVICE_DEMO_NAME) . ':/content', DeviceWorkbook::build($devices), 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            if (empty($result['id'])) throw new RuntimeException('SharePoint ยังไม่ยืนยันไฟล์อุปกรณ์ที่ส่งออก');
            return $result;
        }
        $path = $this->drivePath() . '/items/' . rawurlencode($this->config['export_folder_id']) . ':/' . rawurlencode(self::DEVICE_DEMO_NAME);
        $item = $this->graph('GET', $path . '?$select=id,name,eTag,parentReference');
        if (($item['name'] ?? '') !== self::DEVICE_DEMO_NAME || empty($item['id']) || empty($item['eTag']) || ($item['parentReference']['id'] ?? '') !== $this->config['export_folder_id']) throw new RuntimeException('ไม่พบไฟล์ Excel ทดสอบที่ตรงกับปลายทาง กรุณาตรวจโฟลเดอร์');
        if ($expectedETag !== null && !hash_equals($expectedETag, (string) $item['eTag'])) throw new RuntimeException('Excel ถูกแก้ไขหลังจากดาวน์โหลดนำเข้า กรุณาตรวจไฟล์แล้วกดนำเข้าอีกครั้ง');
        if (preg_match('/[\r\n]/', $item['eTag'])) throw new RuntimeException('ข้อมูลเวอร์ชันไฟล์ไม่ถูกต้อง');
        $body = DeviceWorkbook::build($devices);
        $result = $this->request('PUT', self::GRAPH . $this->drivePath() . '/items/' . rawurlencode($item['id']) . '/content', ['Authorization: Bearer ' . $this->accessToken(), 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'If-Match: ' . $item['eTag']], $body);
        if (($result['id'] ?? '') !== $item['id']) throw new RuntimeException('ยังยืนยันผลอัปเดต Excel ไม่ได้ กรุณาตรวจไฟล์ปลายทางก่อนส่งซ้ำ');
        return $result;
    }

    /** Upload the dedicated user workbook. This contains account metadata only, never passwords. */
    public function syncUserDemo(array $users, ?string $expectedETag = null): array
    {
        if (!$this->canExport()) throw new RuntimeException('กรุณาตั้งค่าการเชื่อมต่อ SharePoint ก่อน');
        $body = UserWorkbook::build($users);
        $target = $this->drivePath() . '/items/' . rawurlencode($this->config['export_folder_id']) . ':/' . rawurlencode(self::USER_DEMO_NAME) . ':/content';
        if ($expectedETag === null) {
            $result = $this->request('PUT', self::GRAPH . $target, ['Authorization: Bearer ' . $this->accessToken(), 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], $body);
        } else {
            $path = $this->drivePath() . '/items/' . rawurlencode($this->config['export_folder_id']) . ':/' . rawurlencode(self::USER_DEMO_NAME);
            $item = $this->graph('GET', $path . '?$select=id,name,eTag,parentReference');
            if (($item['name'] ?? '') !== self::USER_DEMO_NAME || empty($item['id']) || empty($item['eTag']) || ($item['parentReference']['id'] ?? '') !== $this->config['export_folder_id']) throw new RuntimeException('ไม่พบไฟล์ Excel ผู้ใช้งาน DEMO ที่ต้องการอัปเดต');
            if (preg_match('/[\r\n]/', (string)$item['eTag'])) throw new RuntimeException('ข้อมูลเวอร์ชันไฟล์ Excel ผู้ใช้งานไม่ถูกต้อง');
            if (!hash_equals($expectedETag, (string)$item['eTag'])) throw new RuntimeException('Excel ผู้ใช้งานถูกแก้ไขหลังดาวน์โหลด กรุณานำเข้าอีกครั้ง');
            $result = $this->request('PUT', self::GRAPH . $this->drivePath() . '/items/' . rawurlencode($item['id']) . '/content', ['Authorization: Bearer ' . $this->accessToken(), 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'If-Match: ' . $expectedETag], $body);
        }
        if (empty($result['id']) || (isset($result['name']) && $result['name'] !== self::USER_DEMO_NAME)) throw new RuntimeException('SharePoint ยังไม่ยืนยันไฟล์ผู้ใช้งานที่ส่งออก');
        return $result;
    }

    /** @return array{bytes: string, eTag: string} */
    public function downloadUserDemo(): array
    {
        if (!$this->canExport()) throw new RuntimeException('กรุณาตั้งค่าการเชื่อมต่อ SharePoint ก่อน');
        $path = $this->drivePath() . '/items/' . rawurlencode($this->config['export_folder_id']) . ':/' . rawurlencode(self::USER_DEMO_NAME);
        $item = $this->graph('GET', $path . '?$select=id,name,eTag,parentReference');
        if (($item['name'] ?? '') !== self::USER_DEMO_NAME || empty($item['id']) || empty($item['eTag']) || ($item['parentReference']['id'] ?? '') !== $this->config['export_folder_id']) throw new RuntimeException('ไม่พบไฟล์ Excel ผู้ใช้งาน DEMO');
        if (preg_match('/[\r\n]/', (string)$item['eTag'])) throw new RuntimeException('ข้อมูลเวอร์ชันไฟล์ Excel ผู้ใช้งานไม่ถูกต้อง');
        $response = ($this->transport)('GET', self::GRAPH . $this->drivePath() . '/items/' . rawurlencode($item['id']) . '/content', ['Authorization: Bearer ' . $this->accessToken()], '');
        if ((int)($response['status'] ?? 0) !== 302) throw new RuntimeException('SharePoint ยังไม่ส่งลิงก์ดาวน์โหลดไฟล์ผู้ใช้งาน');
        $url = $response['headers']['location'] ?? $response['location'] ?? '';
        $parts = is_string($url) ? parse_url($url) : false;
        if (!$parts || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) throw new RuntimeException('ลิงก์ดาวน์โหลดจาก Microsoft ไม่ถูกต้อง');
        $file = ($this->transport)('GET', $url, [], '');
        if ((int)($file['status'] ?? 0) !== 200 || !is_string($file['body'] ?? null) || strlen($file['body']) > 10*1024*1024) throw new RuntimeException('ดาวน์โหลดไฟล์ Excel ผู้ใช้งานไม่สำเร็จหรือไฟล์ใหญ่เกิน 10 MB');
        $latest = $this->graph('GET', $path . '?$select=id,name,eTag,parentReference');
        if (($latest['eTag'] ?? '') !== $item['eTag']) throw new RuntimeException('ไฟล์ Excel ผู้ใช้งานเปลี่ยนระหว่างดาวน์โหลด กรุณานำเข้าอีกครั้ง');
        return ['bytes'=>$file['body'],'eTag'=>(string)$latest['eTag']];
    }

    /** @return array{bytes: string, eTag: string} */
    public function downloadDeviceDemo(): array
    {
        if (!$this->canExport()) throw new RuntimeException('กรุณาตั้งค่าการเชื่อมต่อ SharePoint ก่อน');
        $path = $this->drivePath() . '/items/' . rawurlencode($this->config['export_folder_id']) . ':/' . rawurlencode(self::DEVICE_DEMO_NAME);
        $item = $this->graph('GET', $path . '?$select=id,name,eTag,parentReference');
        if (($item['name'] ?? '') !== self::DEVICE_DEMO_NAME || empty($item['id']) || empty($item['eTag']) || ($item['parentReference']['id'] ?? '') !== $this->config['export_folder_id']) throw new RuntimeException('ไม่พบไฟล์ DEMO ที่ต้องการนำเข้า');
        $headers = ['Authorization: Bearer ' . $this->accessToken()];
        $response = ($this->transport)('GET', self::GRAPH . $this->drivePath() . '/items/' . rawurlencode($item['id']) . '/content', $headers, '');
        if ((int) ($response['status'] ?? 0) !== 302) throw new RuntimeException('SharePoint ยังไม่ส่งลิงก์ดาวน์โหลดไฟล์ (HTTP ' . (int) ($response['status'] ?? 0) . ')');
        $url = $response['headers']['location'] ?? $response['location'] ?? '';
        $parts = is_string($url) ? parse_url($url) : false;
        if (!$parts || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) throw new RuntimeException('ลิงก์ดาวน์โหลดจาก Microsoft ไม่ถูกต้อง');
        $file = ($this->transport)('GET', $url, [], '');
        if ((int) ($file['status'] ?? 0) !== 200 || !is_string($file['body'] ?? null)) throw new RuntimeException('ดาวน์โหลดไฟล์ Excel จาก SharePoint ไม่สำเร็จ');
        if (strlen($file['body']) > 10 * 1024 * 1024) throw new RuntimeException('ไฟล์ Excel ใหญ่เกิน 10 MB');
        $latest = $this->graph('GET', $path . '?$select=id,name,eTag,parentReference');
        if (($item['eTag'] ?? '') !== ($latest['eTag'] ?? '')) throw new RuntimeException('ไฟล์ Excel เปลี่ยนระหว่างดาวน์โหลด กรุณากดนำเข้าอีกครั้ง');
        return ['bytes' => $file['body'], 'eTag' => (string) ($latest['eTag'] ?? '')];
    }

    public function exportTable(string $type, array $rows): array
    {
        if (!$this->canExport()) throw new RuntimeException('กรุณาตั้งค่าการเชื่อมต่อและโฟลเดอร์ส่งออกก่อน');
        $csv = SharePointTables::csv($type, $rows);
        $filename = $type . '-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.csv';
        $result = $this->graph('PUT', $this->drivePath() . '/items/' . rawurlencode($this->config['export_folder_id']) . ':/' . rawurlencode($filename) . ':/content', $csv, 'text/csv; charset=utf-8');
        if (empty($result['id'])) throw new RuntimeException('SharePoint ไม่ยืนยันไฟล์ที่อัปโหลด กรุณาตรวจโฟลเดอร์ปลายทาง');
        return $result;
    }

    public function uploadRecordPdf(int $recordId, string $storedName): array
    {
        if (!$this->canExport()) throw new RuntimeException('ยังไม่ได้ตั้งค่าปลายทาง SharePoint');
        if ($recordId < 1) throw new RuntimeException('รหัส PM ไม่ถูกต้อง');
        $path = FileUploader::resolve(UPLOAD_PM_RECORD_PATH, $storedName);
        if ($path === null) throw new RuntimeException('ไม่พบไฟล์ PDF ของรายการ PM');
        $size = filesize($path);
        if (!$size || $size > 10 * 1024 * 1024) throw new RuntimeException('ไฟล์ PDF ต้องมีขนาดไม่เกิน 10 MB');
        if ((new finfo(FILEINFO_MIME_TYPE))->file($path) !== 'application/pdf') throw new RuntimeException('ไฟล์แนบไม่ใช่ PDF');
        $body = file_get_contents($path);
        if ($body === false) throw new RuntimeException('อ่านไฟล์ PDF ไม่สำเร็จ');
        // Stable name makes retry replace the same attachment, not create duplicates.
        $name = 'PM-record-' . $recordId . '-' . $storedName;
        $result = $this->graph('PUT', $this->drivePath() . '/items/' . rawurlencode($this->config['export_folder_id']) . ':/' . rawurlencode($name) . ':/content', $body, 'application/pdf');
        if (empty($result['id'])) throw new RuntimeException('SharePoint ไม่ยืนยันไฟล์ที่อัปโหลด กรุณาตรวจโฟลเดอร์ปลายทาง');
        return $result;
    }

    public static function recordsCsv(array $records): string
    {
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) throw new RuntimeException('ไม่สามารถสร้างรายงานได้');
        try {
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['รหัส PM', 'วันที่ดำเนินการ', 'อุปกรณ์', 'แผน PM', 'ผู้ดำเนินการ', 'ผลดำเนินการ', 'หมายเหตุ'], ',', '"', '');
            foreach ($records as $row) {
                $values = [$row['pm_record_id'], $row['performed_date'], $row['svp_device_name'], $row['pm_title'], trim($row['first_name'] . ' ' . $row['last_name']), $row['result_status'], $row['notes'] ?? ''];
                $values = array_map(static function ($value) {
                    $text = (string) $value;
                    // Prevent spreadsheet formula execution, including leading whitespace/control characters.
                    return preg_match('/^[\x00-\x20]*[=+@-]|^[\t\r\n]/u', $text) ? "'" . $text : $text;
                }, $values);
                fputcsv($stream, $values, ',', '"', '');
            }
            rewind($stream);
            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    private function accessToken(): string
    {
        if ($this->missingSettings()) throw new RuntimeException('ยังไม่ได้ตั้งค่าการเชื่อมต่อ SharePoint ครบถ้วน');
        if ($this->token !== null && time() < $this->expires) return $this->token;
        $tenant = $this->config['tenant_id'];
        if (!preg_match('/^[a-zA-Z0-9.-]+$/D', $tenant) || in_array(strtolower($tenant), ['common', 'organizations', 'consumers'], true)) throw new RuntimeException('Tenant ID ไม่ถูกต้อง');
        $data = $this->request('POST', 'https://login.microsoftonline.com/' . rawurlencode($tenant) . '/oauth2/v2.0/token', ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
            'client_id' => $this->config['client_id'], 'client_secret' => $this->config['client_secret'],
            'scope' => 'https://graph.microsoft.com/.default', 'grant_type' => 'client_credentials',
        ], '', '&', PHP_QUERY_RFC3986));
        if (empty($data['access_token']) || !is_string($data['access_token']) || preg_match('/[\r\n]/', $data['access_token'])) throw new RuntimeException('Microsoft ไม่ส่ง access token ที่ใช้ได้');
        $this->token = $data['access_token'];
        $this->expires = time() + max(0, (int) ($data['expires_in'] ?? 0) - 60);
        return $this->token;
    }

    private function graph(string $method, string $path, string $body = '', string $contentType = 'application/json'): array
    {
        return $this->request($method, self::GRAPH . $path, ['Authorization: Bearer ' . $this->accessToken(), 'Accept: application/json', 'Content-Type: ' . $contentType], $body);
    }

    private function request(string $method, string $url, array $headers, string $body): array
    {
        $response = ($this->transport)($method, $url, $headers, $body);
        $status = (int) $response['status'];
        if ($status < 200 || $status >= 300) {
            // Do not expose upstream bodies, URLs, credentials or bearer tokens in errors.
            $message = match ($status) {
                400 => 'Microsoft ปฏิเสธคำขอ กรุณาตรวจสอบ Tenant, App และการตั้งค่า',
                401 => 'ยืนยันตัวตน Microsoft ไม่สำเร็จ กรุณาตรวจสอบ Client ID และอายุ Client Secret',
                403 => 'ไม่มีสิทธิ์ SharePoint กรุณาตรวจสอบ Admin consent และสิทธิ์ของ Site',
                404 => 'ไม่พบ Site, Document Library หรือโฟลเดอร์ที่ระบุ',
                412 => 'ไฟล์ Excel ถูกแก้ไขระหว่างส่งข้อมูล กรุณาตรวจไฟล์แล้วลองใหม่',
                423 => 'ไฟล์ Excel ถูกล็อก กรุณาปิดไฟล์แล้วลองใหม่',
                429 => 'Microsoft จำกัดจำนวนคำขอ กรุณารอสักครู่แล้วลองใหม่',
                default => 'เชื่อมต่อ Microsoft ไม่สำเร็จ กรุณาลองใหม่ภายหลัง',
            };
            throw new RuntimeException($message . ' (HTTP ' . $status . ')');
        }
        $data = json_decode($response['body'], true);
        if (!is_array($data)) throw new RuntimeException('Microsoft ส่งข้อมูลตอบกลับไม่ถูกต้อง');
        return $data;
    }

    private static function curlRequest(string $method, string $url, array $headers, string $body): array
    {
        if (!extension_loaded('curl')) throw new RuntimeException('กรุณาเปิด PHP extension curl บนเซิร์ฟเวอร์');
        $responseHeaders = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
                $length = strlen($line);
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $responseHeaders[strtolower(trim($name))] = trim($value);
                }
                return $length;
            }]);
        if ($method !== 'GET') curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        $result = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($result === false) throw new RuntimeException('ติดต่อ Microsoft ไม่ได้ กรุณาตรวจสอบอินเทอร์เน็ตและใบรับรอง CA ของ PHP');
        return ['status' => $status, 'body' => $result, 'headers' => $responseHeaders];
    }
}
