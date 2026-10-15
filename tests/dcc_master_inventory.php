<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__).'/app/Config/config.php';
require BASE_PATH.'/app/Core/SharePointClient.php';
$client = new SharePointClient();
$id = '012SR5OU6IN2PAE6BZHRFY3XEABFBZ6UN5';
$item = $client->dccItem($id);
if ($item['name'] !== '00. DC-F-002-01 Master List.xlsx') throw new RuntimeException('Source changed');
$drive = (new ReflectionMethod($client, 'drivePath'))->invoke($client);
$meta = (new ReflectionMethod($client, 'graph'))->invoke($client, 'GET', $drive.'/items/'.$id);
$url = $meta['@microsoft.graph.downloadUrl'] ?? '';
if (parse_url($url, PHP_URL_SCHEME) !== 'https') throw new RuntimeException('Invalid download URL');
$curl = curl_init($url);
curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>60, CURLOPT_FOLLOWLOCATION=>false]);
$bytes = curl_exec($curl);
if (curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200 || !is_string($bytes) || strlen($bytes)>10*1024*1024) throw new RuntimeException('Download failed');
curl_close($curl);
$path = sys_get_temp_dir().'/svp-dcc-master-20261015.xlsx';
file_put_contents($path,$bytes);
echo $path.PHP_EOL;
