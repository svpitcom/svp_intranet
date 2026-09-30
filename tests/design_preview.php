<?php
// Render synthetic UI fixtures only. No database access or authenticated session is created.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
define('BASE_PATH', dirname(__DIR__));
define('APP_URL', 'http://localhost/svp_intranet');
define('APP_NAME', 'SVP Intranet');
require BASE_PATH . '/app/Core/Session.php';
require BASE_PATH . '/app/Core/Search.php';
require BASE_PATH . '/app/Core/Validation.php';
require BASE_PATH . '/app/Core/PmCalendar.php';
set_error_handler(function ($level, $message, $file, $line) { throw new ErrorException($message, 0, $level, $file, $line); });
$view = $argv[1] ?? 'users/index';
$allowed = ['users/index','users/form','departments/index','departments/form','positions/index','positions/form','device_types/index','device_types/form','devices/index','devices/form','pm_schedules/index','pm_schedules/form','pm_records/index','pm_records/create'];
if (!in_array($view, $allowed, true)) throw new RuntimeException('Unknown fixture');
$person = ['svp_user_id'=>1,'first_name'=>'สมชาย','last_name'=>'ตัวอย่าง','username'=>'sample.user','email'=>'sample@example.com','user_role'=>'admin','is_active'=>1,'svp_department_name'=>'เทคโนโลยีสารสนเทศ','position_name'=>'เจ้าหน้าที่ IT'];
$_SESSION = ['user'=>$person];
$_GET = [];
$_SERVER['REQUEST_URI'] = '/svp_intranet/' . str_replace('_', '-', explode('/', $view)[0]);
if (str_starts_with($view, 'device_types/')) $_SERVER['REQUEST_URI'] = '/svp_intranet/device_types';
$currentUser = $person;
$users = [$person, array_replace($person, ['svp_user_id'=>2,'first_name'=>'วิภา','last_name'=>'ตัวอย่าง','username'=>'sample.staff','email'=>'staff@example.com','user_role'=>'user'])];
$departments = [['svp_department_id'=>1,'svp_code_department'=>'IT','svp_department_name'=>'เทคโนโลยีสารสนเทศ']];
$positions = [['svp_position_id'=>1,'position_name'=>'เจ้าหน้าที่ IT']];
$devicetypes = $deviceTypes = [['device_type_id'=>1,'device_type_name'=>'คอมพิวเตอร์']];
$devices = [['svp_device_id'=>1,'svp_device_name'=>'SVP-NB-01','brand_name'=>'Lenovo','model_name'=>'ThinkPad','serial_number'=>'SVP-0001','is_active'=>1,'device_type_name'=>'คอมพิวเตอร์','svp_department_name'=>'เทคโนโลยีสารสนเทศ','first_name'=>'สมชาย','last_name'=>'ตัวอย่าง']];
$scheduleSample = ['pm_schedule_id'=>1,'pm_title'=>'ตรวจเช็คคอมพิวเตอร์ประจำเดือน','svp_device_name'=>'SVP-NB-01','svp_device_id'=>1,'frequency_days'=>30,'first_name'=>'สมชาย','last_name'=>'ตัวอย่าง','next_pm_date'=>'2026-10-10','pm_status'=>'soon','checklist'=>"ตรวจสอบสภาพทั่วไป\nทำความสะอาดอุปกรณ์",'is_active'=>1];
$schedules = [$scheduleSample];
$records = $history = [['pm_record_id'=>1,'performed_date'=>'2026-09-30','result_status'=>'completed','first_name'=>'สมชาย','last_name'=>'ตัวอย่าง','notes'=>'อุปกรณ์พร้อมใช้งาน','attachment_path'=>null,'pm_title'=>$scheduleSample['pm_title'],'svp_device_name'=>'SVP-NB-01']];
$user = $device = $schedule = null;
if ($view === 'positions/form') unset($positions);
if ($view === 'device_types/form') unset($devicetypes);
if ($view === 'pm_records/create') $schedule = $scheduleSample;
$totalUsers=2; $totalDevices=1; $currentPage=1; $totalPages=1; $perPage=10; $sort='svp_user_id'; $dir='asc'; $search='';
ob_start(); require BASE_PATH . '/app/Views/' . $view . '.php'; $content=ob_get_clean();
ob_start(); require BASE_PATH . '/app/Views/layouts/main.php'; $html=ob_get_clean();
// Fixture pages never submit data to production or run live AJAX searches.
$html = str_replace('</body>', '<script>document.addEventListener("submit",e=>e.preventDefault(),true); document.getElementById("user-search-input")?.setAttribute("disabled", "");</script></body>', $html);
$folder = BASE_PATH . '/tmp/design-preview';
if (!is_dir($folder)) mkdir($folder,0755,true);
file_put_contents($folder . '/' . str_replace('/', '-', $view) . '.html', $html);
echo 'Rendered ' . $view . PHP_EOL;
