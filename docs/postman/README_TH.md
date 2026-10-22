# SVP Intranet — Postman

ชุดนี้ครอบคลุมเส้นทางที่ประกาศใน `app/Config/routes.php` ไม่ใช่ Microsoft Graph API โดยตรง และไม่ได้เพิ่ม API ใหม่ให้ระบบ
ระบบปัจจุบันรับฟอร์มและตอบ HTML / Redirect / PDF ไม่ใช่ JSON REST API

## เริ่มใช้งาน

1. ใช้ Postman Desktop รุ่นที่รองรับ `pm.execution.skipRequest()` แล้ว Import `SVP_Intranet.postman_collection.json` และ `SVP_Local.postman_environment.json`
2. เลือก Environment **SVP Intranet - Local** ตั้ง `base_url` เป็น `http://localhost/svp_intranet` (ไม่ใส่ `/` ท้าย URL) หรือ URL ของเซิร์ฟเวอร์ที่เข้าถึงได้
3. กรอก `username` และ `password` ของบัญชีทดสอบในค่าภายในเครื่อง ห้ามแชร์หรือ export รหัสผ่านจริง
4. ส่ง **GET /login** เพื่อรับ Cookie และเก็บ `csrf_token` อัตโนมัติ ต้องเปิด Cookie jar ของ Postman และใช้ host เดิมตลอด
5. ส่ง **POST /login** ตรวจว่าได้ 302 และ Location ชี้หน้าแรก ไม่ใช่ /login จากนั้นส่ง **GET /**
6. เลือก Endpoint ที่ต้องการจากโฟลเดอร์ สามารถเปิด Params ที่ปิดไว้เพื่อค้นหา/กรองข้อมูล

Collection ตั้งไม่ให้ตาม Redirect เพื่อแยกผล Login และข้อผิดพลาดได้ชัดเจน หาก Postman รุ่นที่ใช้ไม่รับค่าจากไฟล์ ให้ปิด Automatically follow redirects ใน Settings ของ request

## เพิ่ม / แก้ไข / ลบ / Sync

- เริ่มกับฐานข้อมูลทดสอบและไฟล์ SharePoint ทดสอบ ไม่ควร Run ทั้ง Collection กับระบบจริง
- POST ทุกตัวนอกจาก Login ถูกข้ามโดยปริยาย รวม Logout และการรับทราบประกาศ
- เมื่อตรวจ Body และเป้าหมายแล้ว ตั้ง `allow_write_request` ให้ตรงชื่อคำขอ เช่น `POST /devices/create` แล้วกด Send เฉพาะรายการนั้น ตัวแปรนี้จะถูกล้างทันที ใช้ได้หนึ่งครั้ง
- กลไกนี้เป็นตัวช่วยป้องกันการกดผิด ไม่ใช่สิทธิ์ฝั่งเซิร์ฟเวอร์ ใช้ Postman ที่รองรับ skipRequest เท่านั้น ไม่ควรรันผ่านเครื่องมือเก่าที่ไม่รองรับสคริปต์
- ID เริ่มต้นเป็น 0 และคำขอที่มี ID ใน URL จะถูกข้ามจนใส่ ID จริง ห้ามเดาสุ่ม ID โดยเฉพาะคำขอลบ
- ค่า Body เป็นตัวอย่าง ต้องปรับให้เป็นข้อมูลของรายการเป้าหมายก่อนส่ง Edit จะเขียนทับค่าฟิลด์ที่ส่งไป
- User create ต้องกำหนด `new_user_password` แยกจากรหัสผ่านที่ใช้ Login; User edit เว้น password ว่างถ้าไม่ต้องการเปลี่ยน
- PM ใช้ `schedule_id` กับ URL pm-schedules และ `record_id` กับ pm-records; date ต้องเป็น YYYY-MM-DD และวันที่ทำ PM ห้ามเป็นอนาคต
- ฟอร์มแนบ PDF ใช้ form-data: `reference_doc` (แผน), `pm_attachment` (บันทึก), `attachment` (งาน) เปิดช่องไฟล์แล้วเลือกไฟล์ในเครื่อง ไม่ต้องตั้ง Content-Type multipart เอง
- work/action รองรับ assign, start, resolve, close, reopen, cancel, approve, reject ตามสถานะและสิทธิ์ อ่านหน้า GET /work/{id} ก่อนเสมอ
- GET หน้าแก้ไข Document Control / Announcement และ GET รายละเอียด Work จะเก็บ version ล่าสุดอัตโนมัติ ต้อง GET รายการเป้าหมายใหม่ก่อน POST ทุกครั้ง อย่านำ version จากรายการอื่นมาใช้
- Sync/Import และ Export SharePoint มีผลต่อฐานข้อมูลหรือไฟล์ภายนอกจริง ไม่ได้ส่งข้อมูลจำลองโดยอัตโนมัติ
- ผล 302 ไม่ได้ยืนยันว่าบันทึกสำเร็จ เพราะ validation ผิดก็ Redirect ได้ ให้ GET ตาม Location แล้วอ่านข้อความแจ้งเตือน พร้อมตรวจรายการที่บันทึกอีกครั้ง

## สิทธิ์และข้อผิดพลาด

- Endpoint แต่ละตัวระบุ middleware ไว้ใน Description; ยังมีการตรวจเจ้าของงาน ผู้อนุมัติ สถานะ และสิทธิ์ระดับรายการอีกด้วย
- เครื่องจักร: ซ่อมบำรุง ID 6 หรือ admin; อุปกรณ์ IT: IT ID 2 หรือ admin
- Document Control: DCC ID 13 หรือ admin; บัญชี DCC ที่ไม่ใช่ admin ถูกจำกัดหน้าอื่นตามนโยบายระบบ
- Admin ไม่ได้ข้ามทุกกฎงานอนุมัติ ต้องเป็นผู้อนุมัติที่ถูกกำหนดในกรณีที่ระบบบังคับ
- 403: ตรวจสิทธิ์ แผนก และ CSRF; ถ้า Session หมดอายุ ให้เริ่ม GET /login ใหม่
- 302 ไป /login: ยังไม่ได้เข้าสู่ระบบหรือ Cookie ไม่ตรง host
- 404: ตรวจ ID/URL; ไฟล์แนบจะดาวน์โหลดได้เมื่อรายการนั้นมีไฟล์และมีสิทธิ์
- ทะเบียนรวมยังเป็นเส้นทางแยก ไม่ใช่ตัวกรองสิทธิ์ข้อมูลทั้งหมดตามแผนก

## ขอบเขตการตรวจสอบ

สร้างจากโค้ดปัจจุบัน พร้อมตรวจว่า route ทุกตัวอยู่ใน Collection ครั้งเดียวและทุก POST มี CSRF ไม่ได้ Login ด้วยบัญชีจริง ไม่ได้ทดสอบการเขียน/ลบ/Sync กับระบบจริง และไม่ได้รับรองผลธุรกิจจาก status code อย่างเดียว

สร้างไฟล์ใหม่เมื่อ routes เปลี่ยน: `C:\xampps\php\php.exe tests\build_postman.php` (รันจากโฟลเดอร์โครงการ)
รายการเส้นทางทั้งหมดอยู่ใน ENDPOINTS.md

อ้างอิงกลไกข้ามคำขอ: [Postman — skipRequest](https://learning.postman.com/latest-v-12/docs/tests-and-scripts/write-scripts/postman-sandbox-reference/pm-execution)
