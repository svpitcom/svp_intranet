# Performance review — 200 users

ตรวจวันที่ 7 ตุลาคม 2026 (Asia/Bangkok) บนเครื่อง XAMPP ปัจจุบัน

## ข้อสรุป

ยังไม่สามารถรับรองรองรับ 200 active users ได้จากหลักฐานปัจจุบัน การอ่านฐานข้อมูลเล็กเร็ว แต่มีคอขวดงาน SharePoint แบบ synchronous, จำนวน worker/connection, session lock และหน้ารายงานที่ดึงข้อมูลทั้งหมด ต้องวัด authenticated load บน staging หลังปรับจุดสำคัญก่อนใช้งานตามเป้าหมาย ไม่ได้แก้การตั้งค่าเซิร์ฟเวอร์หรือฐานข้อมูลในการตรวจรอบนี้

200 คนล็อกอินอยู่ ไม่เท่ากับ 200 HTTP requests ทำงานพร้อมกัน ตัวอย่างสมมติ: คนละ 1 action ทุก 10 วินาทีประมาณ 20 actions/วินาที (แต่ละ action อาจมีหลาย requests) ถ้า 20 requests/วินาทีใช้เวลาเฉลี่ย 5 วินาทีจะมีงานค้างเฉลี่ยราว 100 requests การกดส่งไฟล์มีผลมากกว่าการเปิดรายการธรรมดา

## หลักฐานที่ตรวจ

| รายการ | ผล |
|---|---|
| เครื่องปัจจุบัน | 8 logical processors, RAM ประมาณ 15.3 GiB; ไม่ได้วัด CPU saturation หรือ disk throughput |
| Apache | โหลด mpm_winnt และ php_module; config ที่ include ระบุ ThreadsPerChild 150, KeepAliveTimeout 5 วินาที |
| PHP | CLI 8.2.12; memory_limit ใน php.ini 512M, max_execution_time 120 วินาที |
| OPcache | CLI ไม่ได้โหลด; zend_extension=opcache ใน php.ini ถูก comment; ยังไม่ได้ยืนยันผ่าน Apache SAPI |
| MariaDB | 10.4.32, max_connections 151, innodb_buffer_pool_size 16 MiB |
| DB observation | Max_used_connections 2 ตั้งแต่เริ่ม server uptime ประมาณ 5.1 ชั่วโมง; ไม่มีหลักฐานโหลดสูง |
| Slow query log | OFF; long_query_time 10 วินาที; ค่า Slow_queries=0 ไม่ใช่หลักฐานว่ารองรับโหลดเป้าหมาย |
| ข้อมูล | users 42, device 66, pm_schedule 66, pm_record 12, Document Control 0 |
| ดัชนี | primary keys และ FK-related indexes มีอยู่แล้ว; pm_record ยังไม่มี composite index สำหรับวันที่/สถานะตาม query ที่ใช้งาน |

การจับเวลาเป็นการเรียกเมธอดอ่านผ่าน CLI ต่อเนื่อง 3 ครั้งต่อรายการ รวม 12 ครั้ง ไม่ใช่ HTTP, ไม่มี 200 sessions, ไม่รวม login/middleware/render/browser/network/SharePoint และไม่มีการเขียนข้อมูล

| Model read | เวลา 3 ครั้ง (ms) | จำนวนแถวคืน |
|---|---|---|
| ผู้ใช้หน้าแรก | 1.625 / 0.286 / 0.281 | 10 |
| อุปกรณ์หน้าแรก | 1.286 / 0.291 / 0.270 | 10 |
| แผน PM | 2.804 / 1.409 / 1.126 | 66 |
| ประวัติ PM | 1.199 / 0.378 / 0.315 | 12 |

Apache configuration ที่ตรวจเป็นไฟล์บนดิสก์และ module list ไม่ใช่การวัด worker ที่กำลัง busy จริง ส่วนค่า MariaDB มาจาก server runtime

## จุดที่ควรปรับตามลำดับ

1. งานส่งไฟล์: `PmRecordController::store` commit ฐานข้อมูลแล้วเรียก SharePoint ก่อนตอบกลับ; Excel sync/import ทำงานใน HTTP เช่นกัน `curlRequest` timeout 30 วินาทีต่อ upstream request และงานหนึ่งเรียกหลายครั้ง ทำให้ PHP worker และ connection ฐานข้อมูลที่เปิดอยู่ถูกถือระหว่างรอ ควรแยกเป็น queue/job พร้อมสถานะ pending/success/failed, retry/backoff และกันงานซ้ำ/ส่งพร้อมกันไปไฟล์เดียวกัน ทดสอบ timeout/429 และไฟล์ล็อก ไม่ retry ทั้งการ import ที่ commit แล้วโดยไม่ตรวจผล
2. OPcache และ memory budget: ตรวจ Apache SAPI แล้วเปิด OPcache หากยังไม่ได้โหลด วางงบ RAM ร่วม Apache/PHP/DB/Windows ค่า 512 MB เป็นเพดานต่อ script ไม่ใช่การจองจริง แต่ 150 scripts ที่ใช้ใกล้เพดานอาจเกิน RAM มาก วัด peak memory ของการสร้าง Excel/PDF และจำกัด concurrent jobs
3. DB cache/connections: 16 MiB เล็กสำหรับข้อมูลโตและผู้ใช้มาก ควรทดลอง buffer pool 512 MiB–1 GiB บน staging เครื่องลักษณะเดียวกัน แล้ววัด working set/I/O และ RAM ที่เหลือก่อนปรับจริง ค่า connections ต้องสัมพันธ์กับ worker/job budget; ไม่ควรเพิ่ม ThreadsPerChild หรือ max_connections เป็น 200 อย่างเดียวแล้วถือว่าผ่าน
4. PM pagination: `PmRecord::allWithRelations`, `PmSchedule::allWithRelations` คืนทุกแถว และ controllers ค้นใน PHP ควรค้น/กรอง/แบ่งหน้าใน SQL แยก export เป็นงาน background หรือประมวลผลทีละชุด ประวัติของเอกสาร DCC ยังโหลด snapshot ทั้งหมด ควรแบ่งหน้าเมื่อข้อมูลโต
5. Index/query: พิจารณา EXPLAIN บนข้อมูลจำลองขนาดใช้งานจริง ก่อนเพิ่ม index เช่น pm_record(performed_date,pm_record_id), pm_record(pm_schedule_id,performed_date,pm_record_id), และ composite status/date สำหรับ query completed-current-year ปรับ YEAR(performed_date) เป็น date range ที่เหมาะสม และตรวจ execution plan ของ MariaDB รุ่นใช้งานจริงก่อนตัดสินใจ
6. Session: `Session::start` เปิด file session และไม่ close จนจบ request คำขอจาก session เดียวกันจึงรอกัน โดยเฉพาะขณะส่งไฟล์หรือค้น AJAX ไม่ได้ล็อกผู้ใช้ต่าง session ทั้งระบบ ควรบันทึกข้อมูล session/flash ที่จำเป็นแล้วปล่อย lock ก่อนงานยาว ต้องออกแบบการส่งผลผ่าน job status แทนการเขียน flash หลัง close โดยไม่ตั้งใจ
7. SharePoint DCC: อ่าน metadata เพื่อตรวจ ancestry ทีละระดับและดึง token ใหม่ในแต่ละ PHP request จึงเพิ่ม latency เมื่อโฟลเดอร์ลึก พิจารณา cache token server-side ที่ปลอดภัย และ cache metadata ระยะสั้นที่ผูก drive/folder identity; ยังต้องตรวจขอบเขต DCC เมื่อบันทึก
8. Search: ผู้ใช้และอุปกรณ์มี pagination แล้ว และ user AJAX มี debounce 400 ms แต่ LIKE '%คำ%' มักต้องสแกนเมื่อข้อมูลโต; browser abort ไม่รับรองว่า server หยุดประมวลผลทันที ควรวัด query count/latency ต่อการค้นจริง

สิ่งที่ทำดีแล้ว: PDO prepared statements, indexes สำหรับ join หลัก, แบ่งหน้าผู้ใช้/อุปกรณ์/ทะเบียน DCC, PM ใช้ transaction + lock ต่อ schedule และ commit ก่อนส่งไฟล์, DCC มี optimistic version ป้องกัน lost update

## แผนพิสูจน์ 200 คน

- ใช้ staging และ DB copy ที่ลบข้อมูลส่วนบุคคล มีข้อมูลจำนวนใกล้เป้าหมาย 1–3 ปี ใช้ SharePoint โฟลเดอร์ทดสอบหรือ stub ที่จำลอง latency/429 แยกจากไฟล์จริง
- เครื่องยิงโหลดแยกจาก app server ใช้บัญชีทดสอบและ cookie jar แยกต่อ virtual user ผ่าน login/CSRF จริง ห้ามใช้ session เดียวกันทั้งหมดหรือวัดหน้า login อย่างเดียว
- เริ่ม 10 → 25 → 50 → 100 → 200 active users เพิ่มอย่างค่อยเป็นค่อยไป ให้ think time 5–15 วินาที และค้างแต่ละขั้นอย่างน้อย 5 นาที; ระดับ 200 ค้าง 30 นาทีและทำ soak ต่อเมื่อไม่มีอาการผิดปกติ
- workload เริ่มต้นที่เสนอ: อ่านรายการ/ค้นหา 75%, เปิดรายละเอียด/ประวัติ 15%, บันทึก PM/เอกสาร 8%, งานไฟล์ 2%; ปรับสัดส่วนตามงานจริง และทดสอบ burst 200 requests แยกจาก 200 active users
- เป้าหมายเบื้องต้นเพื่อยืนยันร่วมกัน: p95 หน้าอ่าน <2 วินาที, p99 <5 วินาที, error <1%, ไม่มีข้อมูลสูญหาย/ซ้ำจาก retry งาน background มีสถานะตอบกลับเร็วและวัดเวลาจบงานแยก
- เก็บ RPS, latency p50/p95/p99, Apache busy threads/queue, PHP memory, DB connections/lock waits/deadlocks/slow queries, CPU/RAM/disk/network, Graph 429/timeouts, job queue length; หยุดการเพิ่มโหลดเมื่อเริ่ม swap/error ต่อเนื่อง

ยังไม่ได้ยิงโหลด 200 คน ทดสอบ HTTP หลัง login หรือวัดเวลาส่ง SharePoint พร้อมกัน จึงไม่มีตัวเลข p95/RPS capacity ที่ยืนยันได้ในรอบนี้

## ทำซ้ำและเอกสารอ้างอิง

`php tests/performance_audit.php` เป็น CLI-only, ใช้ transaction READ ONLY, แสดงเฉพาะค่าตั้งต้น จำนวนแถว ดัชนี และเวลา ไม่แสดงชื่อบุคคลหรือ credentials

- https://httpd.apache.org/docs/current/mod/mpm_winnt.html — ThreadsPerChild จำกัด concurrent client connections ไม่ใช่จำนวนคนที่ล็อกอิน
- https://www.php.net/manual/en/function.session-write-close.php — session locking และการปล่อย lock
- https://www.php.net/manual/en/opcache.installation.php — การโหลด OPcache ด้วย zend_extension
