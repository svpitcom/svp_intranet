# SVP Intranet — System diagrams

ตรวจโค้ดและ metadata ของฐานข้อมูลจริงวันที่ 25 ตุลาคม 2026 (Asia/Bangkok)

ปรับคำอธิบายสิทธิ์แจ้งซ่อมวันที่ 26 ตุลาคม 2026: ผู้ใช้ทุกแผนกรวม DCC แจ้งซ่อมได้หลังเข้าสู่ระบบ โดย DCC เปิดเฉพาะงานของตน/ที่ได้รับมอบหมาย ไม่ได้เปิดโมดูลคำขออนุมัติ

เมื่อบันทึกซ่อมเสร็จ ระบบแจ้งเตือนผู้แจ้งใน Intranet ให้ตรวจสอบและยืนยันปิดเคสหรือส่งกลับแก้ไข กระดิ่งด้านบนแสดงจำนวนที่ยังไม่อ่านเมื่อโหลดหน้า DCC อ่านและทำเครื่องหมายแจ้งเตือนงานซ่อมของตน/ที่ได้รับมอบหมายได้ การอ่านแจ้งเตือนไม่ใช่การยืนยันปิดเคส ผู้จัดการ/Admin ยังปิดแทนได้ตามสิทธิ์เดิมโดยบันทึกผู้ดำเนินการจริง

Maintenance รับงานเครื่องจักรสถานะแจ้งใหม่ที่ยังไม่มอบหมายให้ตนเองได้ด้วยปุ่ม “รับงานและเริ่มซ่อม” จากนั้นบันทึกผลด้วย “ซ่อมเสร็จ / แจ้งผู้แจ้งตรวจรับ” การเพิ่มข้อความไม่เปลี่ยนสถานะใบงาน

เอกสารนี้อธิบายระบบที่มีอยู่ในปัจจุบัน ไม่ใช่รายการฟีเจอร์ที่วางแผนไว้ ฐานข้อมูลหลักคือ `svpintranet` มี 28 ตาราง เครื่องจักรและ IT แยก **ตาราง** โดยใช้ฐานข้อมูลและบัญชีผู้ใช้กลางร่วมกัน

ไฟล์ [svp-intranet-system.drawio](diagrams/svp-intranet-system.drawio) เปิดแก้ไขด้วย draw.io ได้ มี 9 หน้า ตรงกับ Diagram ด้านล่าง และมี Mermaid แยกเป็น `diagrams/01-system.mmd` ถึง `09-system.mmd` การแก้เอกสารแล้วรัน `python docs/diagrams/build_system_diagrams.py` จะสร้างไฟล์เหล่านี้ใหม่

## 1. ภาพรวมระบบ

```mermaid
flowchart TB
    U["ผู้ใช้งานผ่าน Web browser"]
    A["เข้าสู่ระบบและตรวจสิทธิ์<br/>สถานะบัญชี · Role · แผนก · CSRF"]
    H["SVP Intranet<br/>Dashboard และ Sidebar"]
    PEOPLE["บุคลากร<br/>ผู้ใช้ · แผนก · ตำแหน่ง"]
    IT["อุปกรณ์ IT และ PM<br/>ทะเบียน · แผน · ผลตรวจ IT-F-005"]
    MACHINE["เครื่องจักรและ PM<br/>ทะเบียน · แผน · ประวัติ"]
    WORK["แจ้งซ่อมและคำขอ<br/>มอบหมาย · ติดตาม · อนุมัติ"]
    COMMS["ประกาศและการแจ้งเตือน<br/>กลุ่มเป้าหมาย · รับทราบ · ติดตามงาน"]
    DCC["Document Control<br/>ทะเบียนเอกสาร · Revision · ประวัติ"]
    KPI["KPI ของแต่ละแผนก<br/>รายเดือน · MR-F-027-01 รายปี"]
    BACKUP["บันทึกตรวจ Backup Server<br/>IT-F-004"]
    DB[("svpintranet<br/>28 ตาราง")]
    EXPORT["สร้างไฟล์ / เชื่อมต่อเอกสาร<br/>Excel · PDF · CSV"]
    GRAPH["SharePointClient<br/>Microsoft Graph"]
    SP["SharePoint<br/>โฟลเดอร์ที่ตั้งค่าไว้"]
    U --> A
    A --> H
    H --> PEOPLE
    H --> IT
    H --> MACHINE
    H --> WORK
    H --> COMMS
    H --> DCC
    H --> KPI
    H --> BACKUP
    PEOPLE --> DB
    IT --> DB
    MACHINE --> DB
    WORK --> DB
    COMMS --> DB
    DCC --> DB
    KPI --> DB
    BACKUP --> DB
    IT --> EXPORT
    KPI --> EXPORT
    BACKUP --> EXPORT
    PEOPLE --> EXPORT
    EXPORT --> GRAPH
    DCC --> GRAPH
    GRAPH --> SP
```

เส้นในภาพรวมแสดงการพึ่งพาของโมดูล ไม่ได้หมายความว่าทุกปุ่มบันทึกส่งไป SharePoint อัตโนมัติ การเชื่อมต่อแต่ละชนิดอยู่ในภาพที่ 9

## 2. โครงสร้างการทำงานของเว็บ

```mermaid
flowchart LR
    B["Browser<br/>HTML · CSS · JavaScript / Bootstrap"]
    ENTRY["Apache / PHP<br/>public/index.php"]
    ROUTER["Router<br/>app/Config/routes.php"]
    GUARD["Middleware และ Policy<br/>Auth · Role · แผนก · DCC · CSRF"]
    C["Controllers<br/>รับข้อมูลและเลือกงาน"]
    M["Models / Services<br/>ตรวจข้อมูล · กฎงาน · Query"]
    DB[("MySQL / MariaDB<br/>svpintranet ผ่าน PDO")]
    VIEW["Views<br/>หน้าเว็บและแบบฟอร์ม"]
    TEMPLATE["Templates / Export classes<br/>XLSX และ PDF"]
    FILES["ไฟล์แนบในเครื่อง Server<br/>ดาวน์โหลดผ่าน Controller"]
    REMOTE["SharePointClient<br/>Microsoft Graph"]
    B --> ENTRY
    ENTRY --> ROUTER
    ROUTER --> GUARD
    GUARD --> C
    C --> M
    M --> DB
    C --> VIEW
    VIEW --> B
    C --> TEMPLATE
    TEMPLATE --> B
    C --> FILES
    C --> REMOTE
```

AuthMiddleware อ่านสถานะผู้ใช้ล่าสุดจากฐานข้อมูลก่อนใช้งาน แต่ละโมดูลมี Policy เพิ่มเติม การตรวจ version และ transaction มีในงานที่รองรับ เช่น KPI, เครื่องจักร, ใบงาน, ประกาศ, Document Control และ Backup; ไม่ควรตีความว่าทุกตารางมี audit หรือ version เหมือนกัน

## 3. ฐานข้อมูล: บุคลากร / อุปกรณ์ IT / PM

สัญลักษณ์สำหรับภาพฐานข้อมูล 3–6: **เส้นทึบ = มี Foreign Key จริง**, **เส้นประ = ความสัมพันธ์ที่โค้ดใช้งาน ไม่มี Foreign Key ในฐานที่ตรวจ** เส้นชี้จากตารางแม่ไปตารางที่อ้างอิง ไม่ใช่ทิศทางการไหลของข้อมูล

```mermaid
flowchart LR
    DEP["department<br/>PK svp_department_id"]
    POS["position<br/>PK svp_position_id"]
    USER["users<br/>PK svp_user_id"]
    TYPE["device_type<br/>PK device_type_id"]
    DEVICE["device<br/>PK svp_device_id"]
    PLAN["pm_schedule<br/>PK pm_schedule_id"]
    RECORD["pm_record<br/>PK pm_record_id"]
    FORM["pm_inspection_form<br/>PK / FK pm_record_id<br/>ข้อมูลใบตรวจ IT-F-005"]
    DEP -->|svp_department_id| USER
    POS -->|svp_position_id| USER
    DEP -->|svp_department_id| DEVICE
    USER -->|svp_user_id| DEVICE
    TYPE -->|device_type_id| DEVICE
    DEVICE -->|svp_device_id| PLAN
    USER -->|responsible_user_id| PLAN
    PLAN -->|pm_schedule_id| RECORD
    USER -->|performed_by| RECORD
    RECORD -->|pm_record_id| FORM
```

อุปกรณ์หนึ่งรายการมีหลายแผน แผนหนึ่งมีหลายประวัติ ประวัติ PM หนึ่งรายการมีแบบ IT-F-005 ได้ไม่เกินหนึ่งชุด ประวัติเก่าที่ไม่มีแบบฟอร์มยังอยู่ใน `pm_record`

## 4. ฐานข้อมูล: เครื่องจักร / แผนและประวัติ PM

```mermaid
flowchart LR
    DEP["department<br/>ข้อมูลแผนกกลาง"]
    USER["users<br/>บัญชีผู้ใช้กลาง"]
    MACHINE["machines<br/>PK id<br/>รหัส · ชื่อ · ที่ตั้ง · ผู้รับผิดชอบ"]
    PLAN["machine_pm_schedules<br/>PK id<br/>รอบ PM · Checklist · กำหนดถัดไป"]
    RECORD["machine_pm_records<br/>PK id<br/>ผลตรวจและ Snapshot"]
    EVENTS["machine_events<br/>PK id<br/>entity_type + entity_id"]
    SCHEMA["machine_schema<br/>PK version<br/>เวอร์ชันโครงสร้างตาราง"]
    DEP -.->|department_id| MACHINE
    USER -.->|owner_id| MACHINE
    MACHINE -->|machine_id| PLAN
    MACHINE -->|machine_id| RECORD
    PLAN -->|schedule_id| RECORD
    USER -.->|responsible_id| PLAN
    USER -.->|performed_by| RECORD
    MACHINE -.->|ทะเบียน| EVENTS
    PLAN -.->|แผน| EVENTS
    RECORD -.->|ผลตรวจ| EVENTS
    USER -.->|actor_id| EVENTS
```

`machine_events` อ้างอิงหลายชนิดรายการผ่าน `entity_type` ไม่ใช่ FK สามชุด ส่วน `machine_schema` เป็นตารางกำกับการติดตั้ง ไม่มีความสัมพันธ์กับรายการเครื่องจักร

ไม่มีการใช้ `device.svp_device_id` แทน `machines.id` แม้เลขเท่ากัน ชื่อเครื่องจักร แผน และรายการตรวจในประวัติเก็บเป็น snapshot เพื่อคงข้อมูลในวันที่บันทึก

## 5. ฐานข้อมูล: ใบงาน / ประกาศ / เอกสาร / Backup

```mermaid
flowchart LR
    USER["users"]
    DEP["department"]
    DEVICE["device"]
    WORK["work_items<br/>PK id<br/>kind = ticket / request"]
    EVENT["work_events<br/>PK id"]
    NOTICE["portal_notifications<br/>PK id"]
    ANN["announcements<br/>PK id"]
    READ["announcement_reads<br/>PK announcement_id + user_id"]
    DOC["controlled_documents<br/>PK id<br/>Metadata + SharePoint file reference"]
    DOCH["controlled_document_history<br/>PK id"]
    BACKUP["backup_checklists<br/>PK id<br/>ข้อมูลรายวันใน payload"]
    BACKUPH["backup_checklist_history<br/>PK id"]
    USER -.->|requester / assignee / approver| WORK
    DEVICE -.->|device_id| WORK
    WORK -.->|work_id| EVENT
    WORK -.->|work_id| NOTICE
    USER -.->|actor_id| EVENT
    USER -.->|user_id| NOTICE
    DEP -.->|department_id| ANN
    USER -.->|author_id| ANN
    ANN -.->|announcement_id| READ
    USER -.->|user_id| READ
    DEP -.->|department_id| DOC
    USER -.->|updated_by| DOC
    DOC -->|document_id| DOCH
    USER -.->|changed_by| DOCH
    USER -.->|updated_by| BACKUP
    BACKUP -.->|form_id| BACKUPH
    USER -.->|actor_id| BACKUPH
```

ใบแจ้งซ่อมและคำขอใช้ตารางเดียวกัน แต่กฎสถานะและผู้มีสิทธิ์ต่างกัน `work_items.device_id` ยังอ้างถึงทะเบียน IT ไม่ใช่ทะเบียน `machines` ใหม่ หมวดงาน “เครื่องจักร” ในใบงานเป็นการจัดประเภท ไม่ใช่การเชื่อม FK ไปยังทะเบียนเครื่องจักร

## 6. ฐานข้อมูล: KPI แผนก

```mermaid
flowchart LR
    DEP["department"]
    USER["users"]
    DEF["kpi_definitions<br/>PK id<br/>เดือน · แผนก · เป้าหมาย · น้ำหนัก"]
    RESULT["kpi_results<br/>PK kpi_id<br/>ผลล่าสุด + version"]
    HISTORY["kpi_history<br/>PK id<br/>ประวัติผลและผู้บันทึก"]
    FORM["kpi_forms<br/>PK id<br/>MR-F-027-01 รายปี"]
    FH["kpi_form_history<br/>PK id<br/>Snapshot ตามฉบับ"]
    DELIVERY["kpi_form_deliveries<br/>PK id<br/>หลักฐานส่ง SharePoint"]
    DEP -.->|department_id| DEF
    USER -.->|created_by| DEF
    DEF -.->|kpi_id : ผลล่าสุด 0 หรือ 1| RESULT
    DEF -.->|kpi_id : หลายครั้ง| HISTORY
    USER -.->|updated_by| RESULT
    USER -.->|actor_id| HISTORY
    DEP -.->|department_id| FORM
    USER -.->|updated_by| FORM
    FORM -.->|form_id| FH
    FORM -.->|form_id| DELIVERY
    USER -.->|actor_id| FH
    USER -.->|sent_by| DELIVERY
```

KPI ใหม่เป็นระดับแผนก (`kpi_definitions.user_id = NULL`) พนักงานรวม DCC สร้างและกรอกผลได้เฉพาะแผนกตนเอง Admin จัดการได้ทุกแผนก ผลรายเดือนกับเอกสารรายปีเป็นคนละชุด **ยังไม่ได้ซิงก์ตัวเลขข้ามกันอัตโนมัติ** ผลอัตโนมัติของรายเดือนอ่าน `pm_record`, `work_items`, `work_events` และแผนกปัจจุบันของผู้ทำงาน ไม่ได้รวม `machine_pm_records`

## 7. Workflow แจ้งซ่อมและคำขออนุมัติ

```mermaid
flowchart LR
    NEW["แจ้งซ่อม<br/>open"]
    ASSIGN["มอบหมายผู้รับงาน<br/>ยังคงสถานะเดิม"]
    DO["เริ่มดำเนินการ<br/>in_progress"]
    DONE["แจ้งซ่อมเสร็จ<br/>resolved"]
    CLOSE["ยืนยันปิดงาน<br/>closed"]
    CANCEL["ยกเลิก<br/>cancelled"]
    REQ["ส่งคำขอ<br/>pending"]
    APPROVE["ผู้อนุมัติที่กำหนด<br/>ต้องไม่ใช่ผู้ขอ"]
    YES["อนุมัติ<br/>approved"]
    NO["ไม่อนุมัติ<br/>rejected"]
    NEW --> ASSIGN
    ASSIGN --> DO
    NEW --> DO
    DO --> DONE
    DONE --> CLOSE
    DONE -->|เปิดงานอีกครั้ง| DO
    NEW --> CANCEL
    DO --> CANCEL
    REQ --> APPROVE
    APPROVE --> YES
    APPROVE --> NO
    REQ -->|ผู้ขอยกเลิก| CANCEL
```

การมอบหมายทำได้ขณะ open/in_progress และไม่เปลี่ยนสถานะ ผู้จัดการ/Admin หรือผู้รับงานดำเนินการซ่อม ส่วนการปิด/เปิดงานอีกครั้งเป็นของผู้ขอหรือผู้จัดการ/Admin การอนุมัติคำขอต้องเป็นผู้อนุมัติที่ระบุและมี role manager/admin ทุกการเปลี่ยนแปลงผ่านกฎใน `WorkItem::actions()` และมี event/notification

## 8. Workflow PM เครื่องจักร

```mermaid
flowchart TB
    M["Maintenance หรือ Admin<br/>เพิ่มทะเบียนเครื่องจักร"]
    P["สร้างแผน PM<br/>Checklist · รอบวัน · ผู้รับผิดชอบ"]
    CHECK["กรอกวันทำจริงและผลตรวจ<br/>ตรวจสิทธิ์ · สถานะเปิดใช้ · version"]
    RESULT{"ผลตรวจ"}
    OK["completed<br/>ตรวจครบและปกติทุกข้อ"]
    PART["partial / issue_found<br/>ยังไม่ครบหรือพบปัญหา"]
    ADVANCE["อัปเดตวัน PM ล่าสุดและกำหนดถัดไป<br/>ไม่ย้อนวันล่าสุดเมื่อกรอกประวัติเก่ากว่า"]
    HOLD["คงวันครบกำหนดเดิม"]
    SAVE["Transaction<br/>ประวัติ + Snapshot + version + Audit"]
    HISTORY["เปิดดูประวัติ PM เครื่องจักร"]
    M --> P
    P --> CHECK
    CHECK --> RESULT
    RESULT --> OK
    RESULT --> PART
    OK --> ADVANCE
    PART --> HOLD
    ADVANCE --> SAVE
    HOLD --> SAVE
    SAVE --> HISTORY
```

ภาพนี้อธิบายโมดูลเครื่องจักรใหม่ ไม่ใช่การยืนยันว่าการตรวจ version/active-state ของ PM ฝั่ง IT ใช้ implementation เดียวกัน ไม่สร้างผลตรวจสำเร็จจากแผนในอนาคต

## 9. Excel / PDF / SharePoint

```mermaid
flowchart LR
    USER["ทะเบียนผู้ใช้ / อุปกรณ์ IT"]
    WB["UserWorkbook / DeviceWorkbook<br/>Build · Parse · Import"]
    MASTER["app/Templates/*.xlsx"]
    ANNUAL["KPI รายปีที่บันทึกแล้ว"]
    KEXPORT["KpiFormExport<br/>Excel / PDF"]
    BACKUP["IT-F-004<br/>BackupChecklistExport"]
    PMFORM["IT-F-005<br/>PmInspectionExport"]
    DOWNLOAD["ดาวน์โหลดไฟล์ใน Browser"]
    PMPDF["ไฟล์ PDF แนบผล PM IT<br/>ส่งหลังบันทึกฐานข้อมูลสำเร็จ"]
    CSV["รายงาน PM / ตารางที่รองรับ<br/>SharePointTables และ Export"]
    CLIENT["SharePointClient"]
    GRAPH["Microsoft Graph<br/>App-only access"]
    SP["SharePoint<br/>Site / Drive / โฟลเดอร์ที่ตั้งค่า"]
    RECEIPT["kpi_form_deliveries<br/>เก็บหลักฐานส่งตามฉบับ"]
    DCC["Document Control<br/>อ่านไฟล์ในขอบเขต DCC"]
    USER --> WB
    MASTER --> WB
    WB -->|Admin สั่งอัปเดต / นำเข้า| CLIENT
    CLIENT -->|ดาวน์โหลด XLSX เพื่อนำเข้า| WB
    ANNUAL --> KEXPORT
    MASTER --> KEXPORT
    KEXPORT --> DOWNLOAD
    KEXPORT -->|Admin กดส่งฉบับที่บันทึกแล้ว| CLIENT
    BACKUP --> DOWNLOAD
    PMFORM --> DOWNLOAD
    MASTER --> BACKUP
    MASTER --> PMFORM
    PMPDF --> CLIENT
    CSV -->|Admin สั่งส่งออก| CLIENT
    DCC --> CLIENT
    CLIENT --> GRAPH
    GRAPH --> SP
    CLIENT -->|ผลส่ง KPI สำเร็จ| RECEIPT
```

- KPI: บันทึกฟอร์มก่อน แล้ว Admin เลือกชนิดไฟล์และปลายทางเพื่อส่ง ไม่ส่งทุกครั้งที่พนักงานบันทึก
- PM IT: PDF ที่ผู้ใช้แนบถูกส่งหลัง commit และมีปุ่มให้ Admin ส่งซ้ำ การ Export ใบตรวจ IT-F-005 เป็นอีกการทำงานหนึ่ง ไม่ใช่การส่งใบตรวจที่สร้างใหม่อัตโนมัติ
- Backup IT-F-004: บันทึกและ Export ในเครื่อง ยังไม่มีเส้นทางส่ง SharePoint ของโมดูลนี้
- PM เครื่องจักร: ยังไม่มี Export/SharePoint ของโมดูลใหม่
- การมีโค้ดเชื่อมต่อไม่ใช่หลักฐานว่าบริการภายนอกพร้อมใช้งาน ณ เวลานี้ งาน Diagram นี้ไม่ได้ส่งไฟล์หรือทดสอบ Microsoft Graph จริง

## 10. สิทธิ์ใช้งานตามโค้ดปัจจุบัน

| ส่วนงาน | ผู้มีสิทธิ์หลัก | ขอบเขต |
|---|---|---|
| จัดการผู้ใช้/แผนก/ตำแหน่ง/ประเภทอุปกรณ์ | Admin | เพิ่ม แก้ไข ลบตาม route |
| ทะเบียนอุปกรณ์รวม | ผู้เข้าสู่ระบบที่ไม่ถูกจำกัด DCC | ดู; เพิ่ม/แก้ไข/ลบเป็น Admin |
| ตัวกรองเฉพาะอุปกรณ์ IT | แผนก IT (2) และ Admin | `/it-devices` หรือ group=it |
| แผน PM ฝั่ง IT/ทะเบียนเดิม | ผู้เข้าสู่ระบบที่ไม่ถูกจำกัด DCC | เพิ่ม/แก้ไขแผนเป็น Admin/manager; ลบเป็น Admin |
| บันทึก/ดู PM ฝั่ง IT | ผู้เข้าสู่ระบบที่ไม่ถูกจำกัด DCC | ตรวจตาม route และ validation ของโมดูลเดิม |
| เครื่องจักรและ PM | Maintenance (6) ทุก role และ Admin | เพิ่ม/แก้ไขทะเบียน แผน และบันทึกผล |
| Backup IT-F-004 | IT (2) และ Admin | สร้าง แก้ไข กรอกผล ส่งออก |
| KPI รายเดือน/รายปี | พนักงานทุกแผนก รวม DCC | เฉพาะแผนกตน; Admin ทุกแผนก |
| แจ้งซ่อม | ผู้เข้าสู่ระบบทุกแผนก รวม DCC และผู้ยังไม่สังกัดแผนก | สร้างได้ทุกคน; ผู้ขอ/ผู้รับงานเห็นงานที่เกี่ยวข้อง; Maintenance เห็นคิวเครื่องจักรแม้ยังไม่มอบหมาย; manager ที่ไม่อยู่ใน DCC และ Admin จัดการภาพรวม |
| คำขออนุมัติ | ผู้ขอ/ผู้อนุมัติ/Admin | อนุมัติต้องเป็นผู้อนุมัติที่ระบุ มี role manager/admin และไม่ใช่ผู้ขอ |
| ประกาศ | ผู้มีสิทธิ์และอยู่ในกลุ่มเป้าหมาย | manager/Admin จัดการ; DCC non-admin ยังจำกัดตาม workspace |
| Document Control | DCC (13) และ Admin | ทะเบียนเอกสารและไฟล์ในขอบเขตที่กำหนด |
| SharePoint ทั่วไป / ส่ง KPI | Admin | พนักงานกรอก KPI ได้ แต่กดส่ง SharePoint ไม่ได้ |

สิทธิ์เป็นการผสม role + แผนก + สถานะบัญชี + เจ้าของรายการ จึงไม่ควรใช้ภาพ Role อย่างเดียวแทนเงื่อนไขใน Controller/Model บัญชีที่ปิดใช้งานถูกปฏิเสธโดย AuthMiddleware

## 11. รายการตารางครบ 28 ตาราง

| กลุ่ม | ตาราง |
|---|---|
| บุคลากร (3) | `department`, `position`, `users` |
| อุปกรณ์ IT และ PM (5) | `device_type`, `device`, `pm_schedule`, `pm_record`, `pm_inspection_form` |
| เครื่องจักร (5) | `machines`, `machine_pm_schedules`, `machine_pm_records`, `machine_events`, `machine_schema` |
| ใบงานและแจ้งเตือน (3) | `work_items`, `work_events`, `portal_notifications` |
| ประกาศ (2) | `announcements`, `announcement_reads` |
| Document Control (2) | `controlled_documents`, `controlled_document_history` |
| Backup (2) | `backup_checklists`, `backup_checklist_history` |
| KPI รายเดือน (3) | `kpi_definitions`, `kpi_results`, `kpi_history` |
| KPI รายปี (3) | `kpi_forms`, `kpi_form_history`, `kpi_form_deliveries` |

Foreign Key จริงที่ตรวจพบมี 14 ความสัมพันธ์ แสดงครบในภาพ 3–5 ส่วนตารางที่ไม่มี FK ยังอาจตรวจความสัมพันธ์ด้วยโค้ด ตารางที่มี payload เก็บรายละเอียดแบบฟอร์ม/snapshot ใน JSON จึงไม่ได้มีตารางลูกสำหรับทุกช่องกรอก

## 12. จุดที่ยังไม่เชื่อมอัตโนมัติ

1. ทะเบียนเครื่องจักรใหม่ → `work_items`: ยังไม่มี `machine_id` สำหรับเลือกเครื่องจักรใหม่ในใบแจ้งซ่อม
2. PM เครื่องจักร → KPI อัตโนมัติ / Dashboard PM เดิม / SharePoint PM เดิม: ยังไม่รวมข้อมูลตารางใหม่
3. KPI รายเดือน ↔ แบบ MR-F-027-01 รายปี: ยังไม่ดึงผลหรือคะแนนข้ามกัน
4. Approved by ในแบบฟอร์ม: เป็นชื่อในเอกสาร ไม่ใช่ workflow อนุมัติหรือลายเซ็นดิจิทัล
5. ฐาน `svp_machines` ที่เคยสร้างแยกไว้ไม่ได้ถูกใช้งานแล้ว; `MachineDatabase::connect()` ใช้ connection ของ `svpintranet`

## แหล่งอ้างอิงภายในโครงการ

- `app/Config/routes.php`, `app/Core/Router.php`, `app/Middleware/*`
- `app/Core/MachineAccess.php`, `app/Core/KpiPolicy.php`, `app/Core/DccWorkspace.php`, `app/Core/DocumentControlAccess.php`, `app/Core/ItDeviceAccess.php`
- `app/Models/*`, `app/Controllers/*`, `app/Core/*Export.php`, `app/Core/SharePointClient.php`
- `database/*.sql` และ `information_schema` ของฐานจริง (ตรวจเฉพาะโครงสร้าง ไม่อ่านข้อมูลผู้ใช้)
- คำอธิบายเพิ่มเติม: `MACHINES.md`, `KPI.md`, `IT_BACKUP.md`, `PM_INSPECTION.md`
