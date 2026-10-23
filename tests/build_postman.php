<?php
// CLI-only artifact generator. Does not bootstrap the app, connect to DB, or send requests.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$routes = require __DIR__.'/../app/Config/routes.php';
$out = __DIR__.'/../docs/postman';
if (!is_dir($out)) mkdir($out, 0777, true);
function eventScript(string $listen, string $code): array {
    return ['listen'=>$listen, 'script'=>['type'=>'text/javascript','exec'=>explode("\n", $code)]];
}
$pre = <<<'JS'
// Only one explicitly selected write request is allowed. Never bulk-run writes.
const isLogin = pm.info.requestName === 'POST /login';
if (pm.request.method === 'POST' && !isLogin) {
    if (pm.environment.get('allow_write_request') !== pm.info.requestName) {
        console.warn('Skipped write. Set allow_write_request to this exact request name for one send.');
        pm.execution.skipRequest();
    } else {
        pm.environment.set('allow_write_request', '');
    }
}
const url = pm.variables.replaceIn(pm.request.url.toString());
if (/\{\{[^}]+\}\}/.test(url) || /\/(?:0)(?:\/|\?|$)/.test(url)) {
    console.warn('Set a valid existing record ID before sending.');
    pm.execution.skipRequest();
}
if (pm.request.method === 'POST' && !pm.environment.get('csrf_token')) {
    console.warn('Run GET /login or an authenticated HTML form to obtain CSRF first.');
    pm.execution.skipRequest();
}
if (isLogin && (!pm.environment.get('username') || !pm.environment.get('password'))) {
    console.warn('Set username and password locally in the selected environment.');
    pm.execution.skipRequest();
}
JS;
$post = <<<'JS'
const html = pm.response.text();
const token = html.match(/name=["']_csrf["'][^>]*value=["']([a-f0-9]+)["']/i);
if (token) pm.environment.set('csrf_token', token[1]);
pm.test('No server error (not proof of business success)', function () {
    pm.expect(pm.response.code).to.be.below(500);
});
if (pm.response.code === 403) console.warn('Check role/department, record permissions and CSRF/session.');
if (pm.response.code === 302) console.info('Inspect Location, then GET the destination and check its flash message. 302 alone does not prove success.');
JS;
$collection = ['info'=>['name'=>'SVP Intranet - All Endpoints','schema'=>'https://schema.getpostman.com/json/collection/v2.1.0/collection.json','description'=>'All routes from app/Config/routes.php. HTML/session application, not a JSON REST API. Import the environment and read README_TH.md. Writes are skipped by default; allow exactly one request via allow_write_request. No passwords, secrets or live cookies included.'], 'auth'=>['type'=>'noauth'], 'event'=>[eventScript('prerequest',$pre),eventScript('test',$post)], 'item'=>[]];
$env = ['base_url'=>'http://localhost/svp_intranet','username'=>'','password'=>'','csrf_token'=>'','allow_write_request'=>'','user_id'=>'0','department_id'=>'0','position_id'=>'0','device_type_id'=>'0','device_id'=>'0','schedule_id'=>'0','record_id'=>'0','work_id'=>'0','announcement_id'=>'0','document_id'=>'0','work_version'=>'','announcement_version'=>'','document_version'=>'','new_user_password'=>'','date'=>'2026-10-22'];
$groups = [];
$env += ['kpi_id'=>'0','kpi_version'=>'0','kpi_period'=>'2026-10','kpi_form_id'=>'0','kpi_form_version'=>'0','kpi_form_year'=>'2026'];
$index = "# Endpoint inventory\n\nGenerated from app/Config/routes.php. Middleware is only the first layer; controller/record/department checks also apply.\n\n| Method | Path | Handler | Middleware |\n|---|---|---|---|\n";
// Put authentication first, logout last. Preserve every declared route exactly once.
$ordered = ['GET /login'=>$routes['GET /login'], 'POST /login'=>$routes['POST /login']];
foreach ($routes as $key=>$handler) if (!isset($ordered[$key]) && $key !== 'POST /logout') $ordered[$key]=$handler;
$ordered['POST /logout']=$routes['POST /logout'];
foreach ($ordered as $key=>$handler) {
    [$method,$path] = explode(' ', $key, 2);
    [$controller,$action] = $handler;
    $segment = explode('/', trim($path,'/'))[0];
    $group = match($segment) {
        'login'=>'01 Authentication', 'logout'=>'99 Logout', ''=>'02 Home',
        'users'=>'03 Users', 'departments'=>'04 Departments', 'positions'=>'05 Positions',
        'devices','it-devices','machines'=>'06 Devices', 'device_types'=>'07 Device types',
        'pm-schedules'=>'08 PM schedules', 'pm-records'=>'09 PM records',
        'tickets','requests','work'=>'10 Tickets and approvals', 'announcements'=>'11 Announcements',
        'notifications'=>'12 Notifications', 'document-control'=>'13 Document Control', 'sharepoint'=>'14 SharePoint', 'kpi'=>'15 KPI',
        default=>throw new RuntimeException('Unmapped route '.$key),
    };
    $id = match($segment) {
        'users'=>'user_id','departments'=>'department_id','positions'=>'position_id','device_types'=>'device_type_id',
        'devices'=>'device_id','pm-schedules'=>'schedule_id','pm-records'=>'record_id','work'=>'work_id',
        'announcements'=>'announcement_id','document-control'=>'document_id','kpi'=>'kpi_id',default=>'id',
    };
    if ($controller === 'KpiFormController') $id='kpi_form_id';
    $urlPath = str_replace('{id}', '{{'.$id.'}}', $path);
    $description = "Handler: $controller::$action\nMiddleware: ".implode(', ', $handler[2] ?? [])."\nReturns HTML, redirect or a file; not a JSON API. All POST requests require a session cookie and _csrf.\n";
    if ($method === 'POST' && $path !== '/login') $description .= "WARNING: changes data/session or writes to SharePoint. Set allow_write_request to '$key' for ONE send only. Read the response destination to verify success.\n";
    if (str_contains($path,'delete')) $description .= "DESTRUCTIVE: verify the record ID and backup first. PM schedule deletion can affect records and attachments.\n";
    if ($segment === 'document-control') $description .= "Active DCC department 13 or admin only. Category and department_id are different fields.\n";
    if ($path === '/machines') $description .= "Maintenance department 6 or admin only.\n";
    if ($path === '/it-devices') $description .= "IT department 2 or admin only.\n";
    $fields = []; $file = null; $query = [];
    if ($method === 'POST') {
        $fields['_csrf']='{{csrf_token}}';
        if ($controller === 'KpiFormController' && $action === 'save') {
            $fields += ['title'=>'Postman test annual KPI','year'=>'{{kpi_form_year}}','department_id'=>'{{department_id}}','version'=>'{{kpi_form_version}}','owner'=>'','qms'=>'1','target'=>'95%','measurement'=>'Completed / total x 100','frequency'=>'Monthly','effective_date'=>'','prepared_by'=>'','approved_by'=>'','activities[0][activity]'=>'Review process','activities[0][months][]'=>'1','results[0][result]'=>'','action_taken'=>'','ytd'=>''];
            $description .= "Saves the ENTIRE form. Fill every activity/result to retain existing data. Load the form first to capture its version.\n";
        }
        if ($path === '/login') $fields += ['username'=>'{{username}}','password'=>'{{password}}'];
        if (in_array($action,['store','update'],true)) {
            $fields += match($controller) {
                'KpiController'=>['period'=>'{{kpi_period}}','title'=>'Postman test KPI','department_id'=>'{{department_id}}','user_id'=>'','source'=>'manual','direction'=>'higher','target'=>'10','weight'=>'1','unit'=>'รายการ'],
                'UserController'=>['username'=>'postman_test_user','password'=>$action==='store'?'{{new_user_password}}':'','first_name'=>'Test','last_name'=>'Postman','email'=>'','svp_position_id'=>'','svp_department_id'=>'','user_role'=>'user'],
                'DepartmentController'=>['svp_code_department'=>'TEST','svp_department_name'=>'Postman Test'],
                'PositionController'=>['position_name'=>'Postman Test'],
                'DeviceTypeController'=>['device_type_name'=>'Postman Test'],
                'DeviceController'=>['asset_group'=>'it','svp_device_name'=>'POSTMAN-TEST','brand_name'=>'','model_name'=>'','serial_number'=>'POSTMAN-TEST-001','device_type_id'=>'','svp_department_id'=>'','svp_user_id'=>''],
                'PmScheduleController'=>['svp_device_id'=>'{{device_id}}','pm_title'=>'Postman test plan','frequency_days'=>'30','responsible_user_id'=>'','checklist[]'=>'ตรวจสภาพทั่วไป'],
                'PmRecordController'=>['performed_date'=>'{{date}}','result_status'=>'partial','notes'=>'Postman test'],
                'DocumentControlController'=>['code'=>'TEST-POSTMAN-001','title'=>'Postman test document','revision'=>'00','status'=>'draft','effective_date'=>'','review_date'=>'','notes'=>'Postman test','file_item_id'=>'','department_id'=>'','category'=>'IT','version'=>$action==='store'?'0':'{{document_version}}'],
                'AnnouncementController'=>['title'=>'Postman test','body'=>'Test announcement','department_id'=>'','status'=>'draft','publish_date'=>'{{date}}','expires_date'=>'','pinned'=>'0','version'=>$action==='store'?'0':'{{announcement_version}}'],
                default=>throw new RuntimeException('Unmapped write '.$key),
            };
            if ($action === 'update' && in_array($controller,['UserController','DeviceController','PmScheduleController'],true)) $fields['is_active']='1';
            if ($controller === 'PmScheduleController') { $fields[$action==='store'?'start_date':'next_pm_date']='{{date}}'; $file='reference_doc'; }
            if ($controller === 'PmRecordController') $file='pm_attachment';
        }
        if (in_array($action,['createTicket','createRequest'],true)) {
            $fields += ['title'=>'Postman test','description'=>'Test request','category'=>$action==='createTicket'?'it':'general','priority'=>'normal','device_id'=>'','location'=>'','due_date'=>'','approver_id'=>$action==='createRequest'?'{{user_id}}':''];
            $file='attachment';
            $description .= "Request approver must be another active manager/admin; user_id is not the logged-in requester.\n";
        }
        if ($action === 'action') $fields += ['action'=>'start','version'=>'{{work_version}}','note'=>'Postman test','assignee_id'=>''];
        if ($action === 'acknowledge') $fields['version']='{{announcement_version}}';
        if ($controller === 'KpiController' && $action === 'result') {
            $fields += ['actual'=>'5','note'=>'Postman test','version'=>'{{kpi_version}}'];
            $description .= "Set kpi_version from the target row's hidden version on GET /kpi (0 only before the first result). Automatic KPIs cannot be manually edited.\n";
        }
        if ($controller === 'SharePointController' && in_array($action,['export','exportTables'],true)) {
            $fields['department_target']='';
            $description .= "department_target: empty = configured default, IT or DCC. Device sync/import always uses IT.\n";
            if ($action === 'exportTables') { $fields['type']='devices'; $description .= "type: devices, device_types, departments, pm_schedules or all.\n"; }
        }
    } else {
        if ($controller === 'KpiFormController' && $action === 'export') $query=['format'=>'xlsx'];
        if (in_array($action,['index','search','tickets','requests','schedules','records'],true)) {
            $query = match($controller) {
                'KpiController'=>['period'=>'{{kpi_period}}'],
                'UserController'=>['search'=>'','page'=>'1','sort'=>'svp_user_id','dir'=>'asc'],
                'DeviceController'=>['search'=>'','page'=>'1','sort'=>'svp_device_id','dir'=>'asc','group'=>''],
                'DepartmentController','PositionController','DeviceTypeController'=>['search'=>''],
                'PmScheduleController'=>['search'=>'','page'=>'1','view'=>'table','month'=>'2026-10','year'=>'2026'],
                'PmRecordController','ExportController'=>['search'=>''],
                'WorkController'=>['q'=>'','status'=>'','page'=>'1','approval'=>'0'],
                'AnnouncementController'=>['q'=>'','page'=>'1','manage'=>'0'],
                'NotificationController'=>['page'=>'1'],
                'DocumentControlController'=>['q'=>'','status'=>'','category'=>'','page'=>'1'],
                'SharePointController'=>['folder'=>'','cursor'=>''],default=>[],
            };
        }
        if ($action === 'files') $query=['folder'=>'','cursor'=>'','category'=>'','document_id'=>''];
        if ($controller === 'DocumentControlController' && in_array($action,['create','edit'],true)) $query=['category'=>'','file'=>''];
        if ($controller === 'DeviceController' && $action === 'create') $query=['group'=>'it'];
    }
    $request=['method'=>$method,'header'=>[], 'url'=>['raw'=>'{{base_url}}'.$urlPath,'host'=>['{{base_url}}'],'path'=>explode('/',ltrim($urlPath,'/'))], 'description'=>$description];
    if ($query) $request['url']['query']=array_map(fn($k,$v)=>['key'=>$k,'value'=>$v,'disabled'=>true],array_keys($query),array_values($query));
    if ($fields) {
        $mode=$file?'formdata':'urlencoded';
        $body=array_map(fn($k,$v)=>['key'=>$k,'value'=>$v,'type'=>'text'],array_keys($fields),array_values($fields));
        if ($file) $body[]=['key'=>$file,'type'=>'file','src'=>[],'disabled'=>true,'description'=>'Optional PDF; choose a local file before enabling.'];
        $request['body']=['mode'=>$mode,$mode=>$body];
    }
    $item=['name'=>$key,'request'=>$request,'response'=>[], 'protocolProfileBehavior'=>['followRedirects'=>false]];
    $versionVar=match($controller) {'WorkController'=>'work_version','AnnouncementController'=>'announcement_version','DocumentControlController'=>'document_version','KpiFormController'=>'kpi_form_version',default=>null};
    if ($method==='GET' && $versionVar && in_array($action,['show','edit'],true)) {
        $item['event']=[eventScript('test',"const v = pm.response.text().match(/name=[\"']version[\"'][^>]*value=[\"']([0-9]+)[\"']/i);\nif (v) pm.environment.set('$versionVar', v[1]);")];
    }
    if ($key==='POST /login') $item['event']=[eventScript('test',"pm.test('Login redirects to home', function () { pm.expect(pm.response.code).to.eql(302); pm.expect(pm.response.headers.get('Location')).to.match(/\\/$/); });")];
    $groups[$group][]=$item;
    $index .= "| $method | `$path` | $controller::$action | ".implode(', ',$handler[2]??[])." |\n";
}
ksort($groups);
foreach ($groups as $name=>$items) $collection['item'][]=['name'=>$name,'item'=>$items];
$values=[];
foreach ($env as $key=>$value) $values[]=['key'=>$key,'value'=>$value,'type'=>in_array($key,['password','csrf_token','new_user_password'],true)?'secret':'default','enabled'=>true];
$environment=['name'=>'SVP Intranet - Local','values'=>$values,'_postman_variable_scope'=>'environment'];
foreach (['SVP_Intranet.postman_collection.json'=>$collection,'SVP_Local.postman_environment.json'=>$environment] as $name=>$data) file_put_contents($out.'/'.$name,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
file_put_contents($out.'/ENDPOINTS.md',$index);
// Offline coverage checks: no live API invocation.
$seen=[];
foreach ($collection['item'] as $folder) foreach ($folder['item'] as $item) {
    $seen[]=$item['name'];
    if ($item['request']['method']==='POST' && $item['request']['body'][$item['request']['body']['mode']][0]['key']!=='_csrf') throw new RuntimeException('Missing CSRF');
}
if (count($seen)!==count($routes) || count(array_unique($seen))!==count($routes) || array_diff(array_keys($routes),$seen)) throw new RuntimeException('Route coverage mismatch');
echo 'PASS: '.count($seen)." routes covered exactly once; POST bodies include CSRF; generated JSON parsed by encoder. No HTTP requests sent.\n";
