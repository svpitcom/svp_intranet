<?php
// Read-only inventory; no load generation, application writes, or row data output.
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require BASE_PATH.'/app/Config/database.php';
spl_autoload_register(function($class) { foreach(['Core','Models'] as $dir) { $p=BASE_PATH.'/app/'.$dir.'/'.$class.'.php'; if(is_file($p)) { require $p; return; } } });
$db=Database::connect();
$out=['php_cli'=>['version'=>PHP_VERSION,'ini'=>php_ini_loaded_file(),'opcache_loaded'=>extension_loaded('Zend OPcache')]];
$out['db_variables']=$db->query("SHOW VARIABLES WHERE Variable_name IN ('version','max_connections','innodb_buffer_pool_size','slow_query_log','long_query_time','thread_cache_size')")->fetchAll();
$out['db_status']=$db->query("SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected','Threads_running','Max_used_connections','Slow_queries','Uptime','Innodb_buffer_pool_reads','Innodb_buffer_pool_read_requests')")->fetchAll();
$db->exec('START TRANSACTION READ ONLY');
try {
    foreach(['users','device','pm_schedule','pm_record','controlled_documents','controlled_document_history'] as $table) {
        $out['tables'][$table]['count']=(int)$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
        $out['tables'][$table]['indexes']=array_map(static fn($r)=>array_intersect_key($r,array_flip(['Key_name','Column_name','Seq_in_index','Non_unique'])),$db->query('SHOW INDEX FROM '.$table)->fetchAll());
    }
    foreach(['user_list'=>fn()=>(new User())->paginateWithRelations(),'device_list'=>fn()=>(new Device())->paginate(),'pm_list'=>fn()=>(new PmSchedule())->allWithRelations(),'pm_history'=>fn()=>(new PmRecord())->allWithRelations()] as $label=>$read) {
        $times=[];
        for($i=0;$i<3;$i++) { $start=hrtime(true); $rows=$read(); $times[]=round((hrtime(true)-$start)/1e6,3); }
        $out['sequential_model_reads'][$label]=['returned_rows'=>count($rows),'milliseconds'=>$times];
    }
} finally { $db->exec('ROLLBACK'); }
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
