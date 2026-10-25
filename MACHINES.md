# Separate machine tables in the Intranet database

ทะเบียนเครื่องจักร แผน PM และประวัติ PM เครื่องจักรแยกจากตาราง device/pm_schedule/pm_record ของระบบ IT โดยทุกตารางอยู่ในฐานข้อมูล Intranet เดียวกัน บัญชีผู้ใช้และแผนกใช้ข้อมูลกลางชุดเดิม

## Storage

`MachineDatabase::connect()` ใช้ `Database::connect()` ของ Intranet โดยตรง ใช้ค่า `DB_*` เดิม ไม่มีฐานข้อมูลหรือ credentials อีกชุด และไม่ใช้ `MACHINE_DB_*`

| ตารางเครื่องจักรในฐาน Intranet | หน้าที่ |
|---|---|
| machines | ทะเบียนเครื่องจักร รหัส ชื่อ ประเภท ยี่ห้อ รุ่น Serial จุดติดตั้ง แผนก ผู้รับผิดชอบ สถานะและ version |
| machine_pm_schedules | แผน ความถี่ checklist วันครบกำหนด/วันที่ทำล่าสุด ผู้รับผิดชอบ สถานะและ version |
| machine_pm_records | ผลตรวจจริง ผู้บันทึก ผลรายข้อ และ snapshot เครื่องจักร/แผน ณ วันที่ทำ |
| machine_events | Audit ของการบันทึกทะเบียน แผน และผลตรวจ เก็บใน transaction เดียวกัน |
| machine_schema | เวอร์ชันโครงสร้างสำหรับการติดตั้ง |

IDs เครื่องจักรและ IDs อุปกรณ์ IT เป็นคนละชุด แม้ตัวเลขเหมือนกันก็ห้ามใช้แทนกัน Foreign keys ของแผน/ประวัติเครื่องจักรอ้างอิง machines และ machine_pm_schedules ส่วน user/department IDs ตรวจจากตารางกลางก่อนบันทึกและเก็บชื่อไว้ด้วย ไม่มีการสำเนาบัญชีหรือรหัสผ่าน

การแยกนี้เป็นการแยกตารางและโมดูล ส่วนสิทธิ์การใช้งานยังตรวจผ่านระบบ Intranet

## Installation

1. ใช้การตั้งค่า `DB_*` เดิมของ Intranet ไม่ต้องสร้างฐานข้อมูลใหม่
2. รัน `C:/xampp/php/php.exe database/install_machines.php` ด้วยบัญชี MySQL ที่มีสิทธิ์ CREATE DATABASE/CREATE TABLE และ ALTER ตาราง device
3. Installer เพิ่มตารางในฐานเดิม และเพิ่ม CHECK `chk_device_no_machine` ป้องกัน asset_group=machine ในตาราง device เดิม ใช้ MySQL/MariaDB ที่บังคับใช้ CHECK constraints
4. หากพบเครื่องจักรในตาราง device เดิม หรือข้อมูลในฐาน svp_machines ที่เคยสร้างไว้ Installer จะหยุดก่อนสร้างตาราง ไม่ลบทิ้งหรือย้ายแบบตัดความสัมพันธ์ ต้องย้ายพร้อมแผน/ประวัติ/ไฟล์แนบก่อน ในการปรับครั้งนี้ไม่มีเครื่องจักรเดิมให้ย้าย
5. Installer รันซ้ำได้ ไม่มีการสร้างเครื่องจักรหรือผล PM ตัวอย่าง

สำรองฐาน Intranet พร้อม source/config ตามกระบวนการ deploy ขององค์กร ตารางเครื่องจักรรวมอยู่ในฐานเดียวกันแล้ว ฐาน svp_machines ที่เคยสร้างแยกไว้ยังว่างและไม่ได้ใช้งาน ไม่ได้สั่งลบฐานนั้น

## Routes / workflow

- `/machines` ทะเบียนเครื่องจักร พร้อมค้นหาและ pagination ที่ SQL
- `/machines/create`, `/machines/{id}/edit` เพิ่ม/แก้ไข/เปิดปิดเครื่องจักร
- `/machine-pm` แผน PM เครื่องจักร; เพิ่มและแก้แผนได้จากหน้านี้
- `/machine-pm/{id}/record` บันทึกวันที่จริงและผลตรวจแต่ละข้อ
- `/machine-pm/records` ประวัติพร้อมค้นหา/แบ่งหน้า
- `/machine-pm/records/{id}` รายละเอียด snapshot ของผลตรวจ

Admin และผู้ใช้แผนก Maintenance ทุกคน (department 6 ไม่จำกัด role) เพิ่ม/แก้ไขทะเบียนเครื่องจักรและแผน PM รวมถึงดูข้อมูลและบันทึกผล PM ได้ แผนกอื่นเข้าไม่ได้ ยังคงตรวจ Auth/CSRF และสถานะบัญชีผ่านระบบกลาง บัญชีที่ปิดใช้งานไม่มีสิทธิ์

ไม่มี hard-delete route ให้ปิดใช้งานเพื่อรักษาประวัติ เครื่องจักรหรือแผนที่ปิดแล้วบันทึก PM ไม่ได้ ทุกผลตรวจเพิ่ม version ของแผน ทำให้ส่งฟอร์มซ้ำ/ฟอร์มเก่าไม่ได้ และไม่ให้เปลี่ยน machine_id ของแผนเดิม

ผล `completed` ต้องกรอกทุกข้อเป็นปกติและจะเลื่อนกำหนดตาม frequency_days; `partial` และ `issue_found` คงกำหนดเดิม ห้ามวันอนาคต การบันทึกย้อนหลังที่เก่ากว่าวันล่าสุดไม่ย้อนกำหนด การบันทึกผล ตารางแผน และ audit ใช้ transaction เดียวกัน

ข้อมูลส่วนหัวของประวัติเป็น snapshot จึงไม่เปลี่ยนเมื่อเปลี่ยนชื่อเครื่องจักร จุดติดตั้ง แผนก หรือ checklist ภายหลัง

## Boundaries

- หน้า IT เดิมและ IT-F-005 ใช้ฐานเดิมต่อไป ไม่มีเครื่องจักรใหม่ปนใน Excel/SharePoint ของทะเบียน IT
- ลิงก์เก่า `/devices?group=machine` เข้าโมดูลเครื่องจักรใหม่ ส่วนฟอร์ม IT ไม่อนุญาตให้เลือกกลุ่ม machine อีก
- งานแจ้งซ่อม/คำขอทั่วไปยังใช้โมดูลกลาง การเลือกเครื่องจักรใหม่ด้วย machine_id ในงานแจ้งซ่อมยังไม่ได้เพิ่มในงานแยกทะเบียน/PM ครั้งนี้
- KPI เดิม, Dashboard PM เดิม และรายงาน SharePoint PM เดิมนับจากตารางฝั่ง IT ไม่ได้รวมตารางเครื่องจักรอัตโนมัติ
- PM เครื่องจักรใช้ checklist ตามแผน ไม่ใช้ใบตรวจคอมพิวเตอร์ IT-F-005 และยังไม่มี export/ไฟล์แนบ/SharePoint สำหรับโมดูลเครื่องจักรใหม่

## Verification

`C:/xampp/php/php.exe tests/machines.php` ใช้ SQLite ในหน่วยความจำฐานเดียวโดยแยกตาราง IT และเครื่องจักร ทดสอบ shared connection, identity ไม่ปนกัน, สิทธิ์ทุก route, CSRF, validation, stale writes, replay, snapshot, active-state, rollback และ pagination

ตัวเลือก output directory ของ tests/machines.php สร้าง HTML preview จากข้อมูลทดสอบเท่านั้น ส่วน `tests/machines_live_read.php` อ่านฐานจริง/constraints/จำนวนรายการโดยไม่แก้ข้อมูล
