<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require BASE_PATH.'/app/Config/database.php';
require BASE_PATH.'/app/Core/DocumentCategory.php';
$db=Database::connect();
if (!$db->query("SHOW COLUMNS FROM controlled_documents LIKE 'category'")->fetch()) {
    $db->exec("ALTER TABLE controlled_documents ADD category VARCHAR(30) NOT NULL DEFAULT '', ADD INDEX document_category (category)");
}
$db->beginTransaction();
try {
    $rows=$db->query("SELECT * FROM controlled_documents WHERE category='' FOR UPDATE")->fetchAll(PDO::FETCH_ASSOC);
    $backup=sys_get_temp_dir().'/svp-dcc-categories-before-'.date('Ymd-His').'.json';
    if(file_put_contents($backup,json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))===false) throw new RuntimeException('Backup failed');
    $update=$db->prepare('UPDATE controlled_documents SET category=?,version=version+1 WHERE id=? AND category=\'\'');
    $history=$db->prepare('INSERT INTO controlled_document_history(document_id,snapshot,changed_by,changed_at) VALUES(?,?,?,?)');
    $count=0;
    foreach($rows as $row) {
        $category=DocumentCategory::infer($row);
        if($category==='') continue;
        $update->execute([$category,$row['id']]);
        $row['category']=$category; $row['version']=(int)$row['version']+1;
        $row['_migration']='document_categories: source sheet or code prefix; automated migration';
        $history->execute([$row['id'],json_encode($row,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),0,date('Y-m-d H:i:s')]);
        $count++;
    }
    $db->commit();
    echo 'Categorized: '.$count.PHP_EOL.'Backup: '.$backup.PHP_EOL;
    echo json_encode($db->query('SELECT category,COUNT(*) AS amount FROM controlled_documents GROUP BY category')->fetchAll(),JSON_UNESCAPED_UNICODE).PHP_EOL;
} catch(Throwable $e) { if($db->inTransaction()) $db->rollBack(); throw $e; }
