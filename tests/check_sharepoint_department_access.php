<?php
// Explicit live, read-only metadata check. Never uploads or changes tenant permissions.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require BASE_PATH.'/app/Core/SharePointClient.php';
require BASE_PATH.'/app/Core/SharePointAccess.php';
$client=new SharePointClient(); $failed=false; $roots=[];
foreach (array_unique(SharePointAccess::DEPARTMENTS) as $code) {
    try {
        $result=$client->departmentFiles($code);
        $roots[$code]=$result['folder']['id'];
        echo $code.': scoped folder verified ('.count($result['items'])." visible entries)\n";
        if ($result['cursor']!=='') {
            $next=$client->departmentFiles($code,$roots[$code],$result['cursor']);
            echo $code.': next page verified ('.count($next['items'])." visible entries)\n";
        }
    } catch (RuntimeException $e) { $failed=true; echo $code.': '.$e->getMessage()."\n"; }
}
if (isset($roots['IT'],$roots['HR'])) {
    try { $client->departmentFiles('IT',$roots['HR']); echo "FAIL: cross-department folder allowed\n"; $failed=true; }
    catch(RuntimeException $e) {
        if ($e->getMessage()!=='ไม่มีสิทธิ์เข้าถึงไฟล์หรือโฟลเดอร์นอกพื้นที่แผนก') { echo "FAIL: cross-department check inconclusive\n"; $failed=true; }
        else echo "PASS: IT cannot browse HR folder\n";
    }
}
exit($failed?1:0);
