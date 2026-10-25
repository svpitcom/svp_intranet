# IT-F-005 computer PM inspection

The supplied `IT-F-005.xlsx` is stored at `app/Templates/it-pm-inspection.xlsx`.
It is applied to schedules whose device has `asset_group=it`. Machinery and unclassified devices retain the generic PM record screen.

## Use

- Open PM schedules → บันทึกผลตรวจ PM. Device name, serial number and department are populated from the selected device; inspector defaults to the signed-in person's name.
- Enter the actual inspection date and start/end times on the same day. No result is selected automatically.
- The nine mandatory inspection lines represent the original seven groups (external cleaning has three checks). All 39 main rows, five work rows and 18 safety rows remain available, using original Excel row numbers. Additional blank rows are collapsed on screen.
- `มี / ไม่มี`, `ปกติ / ผิดปกติ`, work completion status and safety details are independent fields. Absent equipment must have a blank result. An abnormal result requires `issue_found`; incomplete results cannot be submitted as `completed`.
- Completed results advance the next PM date using the existing schedule frequency; partial and issue results retain it. This form does not execute BIOS updates, commands or other inspection methods mentioned in the source document.
- Saved form: `/pm-records/{id}/form`. Excel/PDF: `/pm-records/{id}/form/export?format=xlsx` (or `pdf`). History links open these snapshots. Names on the document are not digital signatures or workflow approvals.
- The existing `/pm-schedules/{id}/export` route produces a blank IT-F-005 PDF for IT equipment; `?format=xlsx` downloads the blank Excel version. Dates and results are not inferred from planned dates.
- Existing PDF attachments and their SharePoint delivery still work. Generated IT-F-005 Excel/PDF files are downloaded; they are not automatically uploaded to SharePoint.

## Storage / deployment

Run `C:/xampp/php/php.exe database/install_pm_inspection_forms.php` before using the updated code. The idempotent migration adds `pm_inspection_form` with a foreign key to `pm_record` and does not modify existing PM records or schedules. Back up the application/database according to the usual deployment process.

The inspection payload is inserted in the same database transaction as its PM record. It stores the device identity, header, template revision and all rows as a snapshot. Renaming a device does not rewrite the saved form. Legacy records remain available but do not receive invented inspection results. Existing schedule deletion semantics remain: deleting a schedule cascades to its records and inspection forms.

Authentication and CSRF use the existing PM routes' policies. Form reads and exports share the existing authenticated PM visibility. The current schedule is locked before saving. This change does not redesign PM permissions or fix unrelated schedule-edit concurrency behavior.

## Exports and checks

`PmInspectionExport` fills the original workbook through `FormTemplateWorkbook`. It adds merges in the original blank entry areas and missing styled cells, preserves the original template file, and stores user input as literal text (not Excel formulas). PDF uses Sarabun and A4 with repeating table headings; long content may add pages. Form notes are limited to 500 characters; cell notes to 300, other cell text to 160, and multiline fields to nine lines to keep exports usable.

Run `C:/xampp/php/php.exe tests/pm_inspections.php` for isolated SQLite/controller/export tests. An optional output directory writes sample XLSX/PDF and a static browser preview with fixture data. It does not create production PM records. Run `tests/backup_checklists.php` when changing the shared workbook helper.
