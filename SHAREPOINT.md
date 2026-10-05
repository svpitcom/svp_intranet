# เชื่อม SVP Intranet กับ SharePoint Online

ระบบนี้ใช้ PHP MVC + MySQL เดิม และเรียก Microsoft Graph v1.0 จากเซิร์ฟเวอร์ ตามแนวคิดในแชต “แนวคิดระบบ Intranet” โดย SharePoint เป็นที่เก็บเอกสาร ไม่ใช่โฮสต์ PHP

## สิ่งที่ใช้งานได้

- หน้า SharePoint มีปุ่มส่งทะเบียนอุปกรณ์ ประเภทอุปกรณ์ แผนก และแผน PM แยกประเภท หรือส่งทั้ง 4 ประเภท แต่ละครั้งสร้าง CSV ใหม่ในโฟลเดอร์ส่งออกเดิม รวมรายการปิดใช้งานด้วย ข้อมูลหลักยังอยู่ใน MySQL และไม่มีการซิงก์อัตโนมัติ หากส่งสำเร็จเพียงบางประเภทจะแจ้งผลแยกกันเพื่อส่งซ้ำเฉพาะที่ขาด
- CSV มีเฉพาะคอลัมน์ทะเบียนที่กำหนด ไม่ส่งรหัสผ่านหรือข้อมูลบัญชีอื่น ชื่อไฟล์ขึ้นต้น `devices-`, `device_types-`, `departments-`, `pm_schedules-` สำหรับ Serial Number ที่มีศูนย์นำหน้า ให้ใช้ Excel Import CSV แล้วตั้งคอลัมน์เป็น Text เพื่อป้องกัน Excel แปลงเป็นตัวเลข

- ผู้ดูแลระบบ (`admin`) เปิดเมนู SharePoint หรือ `/sharepoint` เพื่อตรวจสอบ Site และเรียกดู Document Library ทีละ 50 รายการ พร้อมเปิดโฟลเดอร์และหน้าถัดไป
- เปิดไฟล์ Excel/เอกสารที่ SharePoint ด้วยบัญชี Microsoft 365 ของผู้ใช้
- กดส่งออกประวัติ PM ทั้งหมดจาก MySQL เป็น CSV ภาษาไทยที่เปิดด้วย Excel ได้ ไปยังโฟลเดอร์ที่กำหนด โดยแต่ละครั้งใช้ชื่อใหม่ ประกอบด้วยเวลา UTC และรหัสสุ่ม
- ไฟล์ส่งออกมีรหัส PM, วันที่, อุปกรณ์, แผน, ผู้ดำเนินการ, ผล และหมายเหตุ ไม่รวมไฟล์แนบ

ยังไม่มีการซิงก์สองทาง การแก้ไขแถวใน Excel การย้ายฐานข้อมูล หรือการส่งออกอัตโนมัติ การแก้ไฟล์ใน SharePoint ไม่แก้ MySQL

## ตั้งค่า Microsoft 365

1. ใน Microsoft Entra ID สร้าง App registration แบบ single tenant แล้วจด Directory (tenant) ID และ Application (client) ID
2. สร้าง Client secret แล้วนำ **Value** ไปเก็บใน `.env` ของเซิร์ฟเวอร์เท่านั้น ห้ามส่ง secret ในแชตหรือใส่ไว้ใน JavaScript จดวันหมดอายุเพื่อเปลี่ยนก่อนหมดอายุ
3. เพิ่ม Microsoft Graph **Application permission** `Sites.Selected` แล้วให้ผู้ดูแล tenant ทำ **Grant admin consent**
4. ให้ผู้ดูแล Microsoft 365 อนุญาตแอปเข้าถึง Site ที่ใช้โดยเฉพาะ ด้วย `read` สำหรับดูไฟล์ หรือ `write` สำหรับส่งออก PM การทำ admin consent เพียงอย่างเดียวยังไม่ให้สิทธิ์ Site

ตัวอย่างคำขอที่ผู้ดูแลทำด้วยเครื่องมือ Microsoft Graph ของตน โดยใช้บัญชี/แอปจัดการสิทธิ์ที่มีสิทธิ์เพียงพอ ไม่ใช่แอป Intranet:

```http
POST https://graph.microsoft.com/v1.0/sites/{site-id}/permissions
Content-Type: application/json

{
  "roles": ["write"],
  "grantedToIdentities": [{
    "application": {"id": "INTRANET-CLIENT-ID", "displayName": "SVP Intranet"}
  }]
}
```

5. หา Site ID ด้วย `GET /v1.0/sites/{hostname}:/sites/{site-path}` ผ่านเครื่องมือของผู้ดูแล และหา Document Library ด้วย `GET /v1.0/sites/{site-id}/drives`
6. สร้างโฟลเดอร์รับรายงาน เช่น `PM Reports` ใน library นั้น แล้วหา folder item ID ด้วย `GET /v1.0/drives/{drive-id}/root:/PM%20Reports`
7. นำคีย์ใน `.env.sharepoint.example` ไป **เพิ่มท้าย** `.env` เดิม แล้วใส่ค่าจริง อย่าเขียนทับค่าเชื่อมฐานข้อมูล หากยังไม่ส่งออกให้เว้น `SHAREPOINT_EXPORT_FOLDER_ID`
8. เปิด PHP extension `curl` และตั้ง CA certificate ที่เชื่อถือได้ (`curl.cainfo` หากจำเป็น) โดยคงการตรวจสอบ TLS ไว้ เซิร์ฟเวอร์ต้องออก HTTPS ไป `login.microsoftonline.com` และ `graph.microsoft.com` ได้
9. ล็อกอิน Intranet ด้วย admin แล้วเปิด `/sharepoint` ตรวจชื่อ Site และไฟล์ให้ตรงก่อนกดส่งออก ลองส่งออกและตรวจ CSV ในโฟลเดอร์ที่ตั้งค่าไว้

## ขอบเขตสิทธิ์และการดูแล

เมื่อบันทึก PM พร้อมแนบ PDF ระบบจะส่งไฟล์แนบนั้นไปโฟลเดอร์ที่ตั้งค่าไว้ หลังบันทึกฐานข้อมูลสำเร็จ สำหรับทุกบทบาทที่มีสิทธิ์บันทึก PM ไฟล์ต้นฉบับยังอยู่ในระบบ หาก SharePoint ล่มหรือสิทธิ์ไม่ครบ ระบบแจ้งว่าส่งไม่สำเร็จโดยไม่ย้อนกลับข้อมูล PM ผู้ดูแลสามารถกด “ส่ง PDF ไป SharePoint / ส่งซ้ำ” ในหน้าประวัติ PM ได้ รวมถึงรายการเก่าที่มี PDF ชื่อปลายทางเป็น `PM-record-{id}-{stored-name}.pdf` ส่งซ้ำจะเขียนไฟล์เดียวกัน ไม่สร้างชื่อใหม่ ไม่มีการส่งย้อนหลังอัตโนมัติหรือสร้าง PDF เมื่อไม่ได้แนบไฟล์ และ CSV export ยังคงส่งเฉพาะข้อมูลตาราง

การเชื่อมต่อใช้ app-only client credentials จึงอ่าน metadata ตามสิทธิ์แอป ไม่ใช่สิทธิ์ Microsoft ของผู้ใช้ Intranet หน้าเชื่อมต่อและ POST ส่งออกจึงจำกัดเฉพาะ admin ที่ยัง active และผ่าน CSRF ของระบบเดิม หากต้องการเปิดให้พนักงานทั่วไป ต้องออกแบบสิทธิ์เพิ่มเติมหรือใช้ delegated sign-in ก่อน

ใช้ Site/Library สำหรับข้อมูล Intranet ที่ผู้ดูแลกลุ่มนี้มีสิทธิ์ดูจริง ลิงก์เปิดไฟล์ใช้สิทธิ์บัญชี Microsoft ของผู้เปิดอีกครั้ง ไม่มีลิงก์ anonymous และไม่มีการส่ง access token ให้เบราว์เซอร์ ควรโฮสต์โดยชี้ document root ที่ `public/` และให้เว็บทำงานบน HTTPS

Token เก็บในหน่วยความจำเฉพาะคำขอ PHP และใช้ซ้ำระหว่างการอ่าน Site/รายการไฟล์ภายในคำขอนั้น ไม่มี cache บนดิสก์ ไม่มี retry อัปโหลดอัตโนมัติ หาก timeout ระหว่างส่งออก ให้ตรวจโฟลเดอร์ปลายทางก่อนกดซ้ำ เพราะ Microsoft อาจรับไฟล์แล้ว รายงานจำกัด 10 MB และดึงประวัติทั้งหมดเข้าหน่วยความจำ เหมาะกับข้อมูลขนาดเล็กถึงปานกลาง

HTTP 401: ตรวจ Client ID/Secret และวันหมดอายุ; 403: ตรวจ admin consent และ Site grant; 404: ตรวจ Site/Drive/Folder ID; 429: รอแล้วลองใหม่ ข้อผิดพลาดไม่แสดง response body ของ Microsoft หรือ secret

## การทดสอบ

```text
php tests/sharepoint.php
php tests/sharepoint_tables.php
php tests/sharepoint_pdf.php
php tests/search.php
php tests/calendar.php
```

SharePoint tests ใช้ HTTP จำลองและฐานข้อมูลทดสอบจาก regression suite ไม่ใช้ tenant จริง ครอบคลุม OAuth, อายุ token, pagination, การเข้ารหัส ID, การป้องกัน URL ปลายทางจาก cursor, ข้อผิดพลาด, CSV formula injection, route permissions และ HTML escaping ต้องทดสอบเชื่อมจริงหลังผู้ดูแลตั้งค่าครบ

## เอกสาร Microsoft

- [Client credentials](https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-client-creds-grant-flow)
- [Selected permissions และ Site grants](https://learn.microsoft.com/en-us/graph/permissions-selected-overview)
- [อ่านไฟล์ในโฟลเดอร์](https://learn.microsoft.com/en-us/graph/api/driveitem-list-children?view=graph-rest-1.0)
- [อัปโหลดไฟล์](https://learn.microsoft.com/en-us/graph/api/driveitem-put-content?view=graph-rest-1.0)
