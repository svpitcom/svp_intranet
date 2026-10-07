<?php
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require BASE_PATH.'/app/Config/database.php';
foreach(['Model','SharePointClient'] as $class) require BASE_PATH.'/app/Core/'.$class.'.php';
require BASE_PATH.'/app/Models/ControlledDocument.php';
try {
    $list=(new ControlledDocument())->listing('','',1);
    echo 'Registry ready: '.$list['total']." records\n";
    $page=(new SharePointClient())->dccFiles();
    echo 'DCC readable: '.count($page['items'])." items on first page (read only)\n";
} catch(Throwable $e) { echo "Document Control read check failed\n"; exit(1); }
