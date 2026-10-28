<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
ob_start(); require __DIR__.'/intranet.php';
require_once BASE_PATH.'/app/Middleware/AuthMiddleware.php';
$start=$checks;
function env(string $key,$default=null) { return $default; }
$config=['tenant_id'=>'test','client_id'=>'test','client_secret'=>'test','site_id'=>'site','drive_id'=>'drive','export_folder_id'=>'exports','department_folders'=>[]];
foreach (array_unique(SharePointAccess::DEPARTMENTS) as $code) $config['department_folders'][$code]=strtolower($code);
$calls=[];
$transport=static function($method,$url,$headers,$body) use (&$calls) {
    $calls[]=$method.' '.$url;
    if ($method==='POST' && str_contains($url,'/token')) return ['status'=>200,'body'=>'{"access_token":"test-token","expires_in":3600}'];
    if ($method!=='GET') throw new RuntimeException('Unexpected write');
    $path=parse_url($url,PHP_URL_PATH);
    if (!preg_match('#/items/([^/]+)#',$path,$matches)) throw new RuntimeException('Unexpected library root access');
    $id=rawurldecode($matches[1]);
    parse_str(parse_url($url,PHP_URL_QUERY)??'',$query);
    if (str_ends_with($path,'/children')) {
        $child=['id'=>$id.'-file','name'=>'<test>.docx','file'=>(object)[],'webUrl'=>'https://example.sharepoint.com/file','parentReference'=>['id'=>$id,'driveId'=>'drive']];
        if (($query['$skiptoken']??'')==='cross') $child['parentReference']['id']='other';
        $shortcut=['id'=>'shortcut','name'=>'Other shortcut','remoteItem'=>['id'=>'remote'],'folder'=>(object)[],'parentReference'=>['id'=>$id,'driveId'=>'drive']];
        return ['status'=>200,'body'=>json_encode(['value'=>[$child,$shortcut]])];
    }
    $node=['id'=>$id,'name'=>$id,'folder'=>(object)[],'webUrl'=>'https://example.sharepoint.com/'.$id,'parentReference'=>['driveId'=>'drive','id'=>'library']];
    if ($id==='nested') $node['parentReference']['id']='it';
    if ($id==='cycle') $node['parentReference']['id']='cycle';
    if ($id==='wrong-drive') $node['parentReference']=['id'=>'it','driveId'=>'another'];
    if ($id==='shortcut') { $node['parentReference']['id']='it'; $node['remoteItem']=['id'=>'outside']; }
    return ['status'=>200,'body'=>json_encode($node)];
};
$client=new SharePointClient($config,$transport);
check($client->departmentFiles('IT')['folder']['id']==='it','default starts inside department');
check($client->departmentFiles('IT','nested')['folder']['id']==='nested','nested department folder allowed');
check(count($client->departmentFiles('IT')['items'])===1,'cross-area shortcuts omitted');
foreach (['hr','library','cycle','wrong-drive','shortcut'] as $outside) denied(fn()=>$client->departmentFiles('IT',$outside),'outside folder or shortcut denied '.$outside);
denied(fn()=>$client->departmentFiles('IT','it','cross'),'paging cannot display foreign-folder items');
$before=count($calls); $missing=new SharePointClient(array_replace($config,['department_folders'=>[]]),$transport);
denied(fn()=>$missing->departmentFiles('IT'),'missing department root denied');
check(count($calls)===$before,'missing root never falls back to drive root');
class DepartmentSharePointHarness extends SharePointController {
    public static SharePointClient $mock;
    public static array $last=[];
    protected function client(): SharePointClient { return self::$mock; }
    protected function view(string $view,array $data=[],string $layout='main'): void { self::$last=['view'=>$view,'data'=>$data]; extract($data); require BASE_PATH.'/app/Views/'.$view.'.php'; }
}
DepartmentSharePointHarness::$mock=$client;
$routes=require BASE_PATH.'/app/Config/routes.php';
$routes['GET /sharepoint'][0]='DepartmentSharePointHarness'; $router=new Router($routes);
foreach (SharePointAccess::DEPARTMENTS as $dept=>$code) {
    $uid=100+$dept; $stmt=$db->prepare('INSERT INTO users(svp_user_id,username,first_name,last_name,user_role,is_active,svp_department_id) VALUES(?,?,?,?,?,?,?)');
    $stmt->execute([$uid,'dept-'.$dept,'Department','User','user',1,$dept]);
    $user=(new User())->findById($uid); $_SESSION['user']=$user; $_GET=['department_target'=>'DCC','department_id'=>'13']; $_POST=[];
    ob_start(); $router->dispatch('GET','/sharepoint'); $html=ob_get_clean();
    check(DepartmentSharePointHarness::$last['data']['page']['folder']['id']===strtolower($code),'authenticated department selects its root '.$dept);
    check(!str_contains($html,'/sharepoint/export') && !str_contains($html,'name="department_target"'),'staff cannot see global exports or target picker '.$dept);
    check(str_contains(render('partials/sidebar',[]),APP_URL.'/sharepoint'),'department has SharePoint menu '.$dept);
    check(!str_contains($html,'<test>') && str_contains($html,'&lt;test&gt;'),'file names escaped '.$dept);
}
$_SESSION['user']=(new User())->findById(102); $_GET=['folder'=>'hr','department_target'=>'HR'];
ob_start(); $router->dispatch('GET','/sharepoint'); $html=ob_get_clean();
check(DepartmentSharePointHarness::$last['data']['error']!=='' && !str_contains($html,'<td>hr'),'forged folder rejected through controller');
$db->exec('UPDATE users SET svp_department_id=3 WHERE svp_user_id=102'); $_GET=[];
ob_start(); $router->dispatch('GET','/sharepoint'); ob_end_clean();
check(DepartmentSharePointHarness::$last['data']['page']['folder']['id']==='hr','department change takes effect through refreshed authentication');
$db->exec('UPDATE users SET svp_department_id=NULL WHERE svp_user_id=102'); $before=count($calls);
ob_start(); $router->dispatch('GET','/sharepoint'); ob_end_clean();
check(count($calls)===$before && DepartmentSharePointHarness::$last['data']['page']['items']===[],'unassigned staff get no files and no upstream root request');
check(!SharePointAccess::allows(array_replace($owner,['is_active'=>0])) && !SharePointAccess::allows(null),'inactive or anonymous cannot access');
check(SharePointAccess::admin($admin) && !SharePointAccess::admin($manager),'only admin retains global management');
check(SharePointAccess::department(['is_active'=>1,'svp_department_id'=>'6'])==='MAINTENANCE','string IDs supported');
foreach ($routes as $route=>$handler) if ($handler[0]==='SharePointController' && str_starts_with($route,'POST ')) check(in_array('role:admin',$handler[2],true),'SharePoint write remains admin-only '.$route);
check(!DccWorkspace::allows(['svp_department_id'=>13,'user_role'=>'user'],'POST /sharepoint/export'),'DCC cannot use global export');
echo 'PASS: '.($checks-$start)." department SharePoint checks (mock Graph; no live writes).\n";
ob_end_flush();
