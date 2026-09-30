<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        <?php require __DIR__ . '/_fonts.php'; ?>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 9px;
            color: #000;
            margin: 0;
        }

        .sheet {
            border: 1.3px solid #000;
            padding: 5px;
        }

        /* ---- Header ---- */
        .header-title {
            text-align: center;
            margin-bottom: 3px;
        }

        .header-title .t1 {
            font-size: 12px;
            font-weight: bold;
        }

        .header-title .t2 {
            font-size: 10px;
            font-weight: bold;
            margin-top: 1px;
        }

        .top-checkbox {
            text-align: right;
            font-size: 8.5px;
            margin-bottom: 3px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }

        .header-table td {
            border: none;
            padding: 0 4px;
            vertical-align: middle;
        }

        .logo-cell {
            width: 90px;
            text-align: left;
        }

        .logo-cell img {
            max-width: 85px;
            max-height: 55px;
        }

        .title-cell {
            text-align: center;
        }

        .checkbox-cell {
            width: 110px;
            text-align: right;
            font-size: 8.5px;
        }

        .checkbox {
            display: inline-block;
            width: 9px;
            height: 9px;
            border: 1px solid #000;
            margin-right: 2px;
            vertical-align: middle;
        }

        /* ---- Info fields (2 columns of label:value) ---- */
        table.info-grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 4px;
        }

        table.info-grid td {
            padding: 1.5px 3px;
            border-bottom: 1px dotted #000;
        }

        table.info-grid .lbl {
            white-space: nowrap;
            width: 90px;
        }

        table.info-grid .dots {
            border-bottom: 1px dotted #000;
        }

        /* ---- Main checklist table ---- */
        table.main-grid {
            width: 100%;
            border-collapse: collapse;
        }

        table.main-grid th,
        table.main-grid td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 8.5px;
            vertical-align: middle;
        }

        table.main-grid th {
            background: #fff;
            font-weight: bold;
            text-align: center;
        }

        table.main-grid td.no-col {
            width: 22px;
            text-align: center;
        }

        table.main-grid td.item-col {
            width: 140px;
        }

        table.main-grid td.method-col {
            width: 130px;
        }

        table.main-grid td.remark-col {
            width: 90px;
        }

        table.main-grid td.result-col {
            width: 24px;
            text-align: center;
        }

        table.main-grid td.sign-col {
            width: 60px;
        }

        table.main-grid td.dot-row {
            border-top: 1px dotted #999;
            height: 14px;
        }

        .result-header,
        .fix-header {
            text-align: center;
            font-size: 8px;
        }

        /* ---- Section title rows ---- */
        .section-row td {
            border: 1px solid #000;
            padding: 3px 6px;
            font-weight: bold;
            font-size: 9px;
            background: #fafafa;
        }

        /* ---- Footer ---- */
        .footer-table {
            width: 100%;
            margin-top: 4px;
            font-size: 7.5px;
            color: #333;
        }

        .footer-table td {
            border: none;
            padding: 2px 0;
        }
    </style>
</head>

<body>
    <div class="sheet">

        <div class="top-checkbox">
            <span class="checkbox"></span>ต้นฉบับ &nbsp;&nbsp;
            <span class="checkbox"></span>สำเนาการดำเนินงาน
        </div>

        <div class="header-title">
            <div class="lbl">ใบรายงานผลการตรวจสอบและบำรุงรักษาเชิงป้องกัน</div>
            <div class="t2">SV POLYMER CO., LTD.</div>
        </div>

        <!-- ===== Info fields ===== -->
        <table class="info-grid">
            <tr>
                <td class="lbl">ชื่ออุปกรณ์/เครื่องจักร</td>
                <td class="dots"><?= htmlspecialchars($schedule['svp_device_name']) ?></td>
            </tr>
            <tr>
                <td class="lbl">แผน PM</td>
                <td class="dots"><?= htmlspecialchars($schedule['pm_title']) ?></td>
            </tr>
            <tr>
                <td class="lbl">แผนก</td>
                <td class="dots"><?= htmlspecialchars($schedule['svp_department_name'] ?? '-') ?></td>
            </tr>
            <tr>
                <td class="lbl">วันที่ทำการตรวจสอบ</td>
                <td class="dots">............./............./.............</td>
                <td class="lbl" style="width:110px; text-align:right;">รอบความถี่</td>
                <td class="dots" style="width:150px;">ทุก <?= (int) $schedule['frequency_days'] ?> วัน</td>
            </tr>
            <tr>
                <td class="lbl">ผู้จัดทำ</td>
                <td class="dots">......................................................</td>
                <td class="lbl" style="width:110px; text-align:right;">ผู้รับผิดชอบ</td>
                <td class="dots" style="width:150px;">
                    <?= htmlspecialchars(trim(($schedule['first_name'] ?? '') . ' ' . ($schedule['last_name'] ?? '')) ?: '-') ?>
                </td>
            </tr>
        </table>

        <!-- ===== Main checklist table ===== -->
        <table class="main-grid">
            <thead>
                <tr>
                    <th rowspan="2">NO.</th>
                    <th rowspan="2">รายการที่ทำการตรวจ</th>
                    <th rowspan="2">วิธีการดำเนินการ</th>
                    <th rowspan="2">หมายเหตุ</th>
                    <th colspan="2">ผลการตรวจ</th>
                    <th colspan="2">รายละเอียดการแก้ไข</th>
                    <th rowspan="2">รายมือชื่อ</th>
                </tr>
                <tr>
                    <th class="result-col">ปกติ</th>
                    <th class="result-col">ผิดปกติ</th>
                    <th class="result-col">แก้ไข</th>
                    <th class="result-col">ไม่แก้ไข</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checklistItems as $i => $item): ?>
                    <tr>
                        <td class="no-col"><?= $i + 1 ?></td>
                        <td class="item-col"><?= htmlspecialchars($item) ?></td>
                        <td class="method-col">&nbsp;</td>
                        <td class="remark-col">&nbsp;</td>
                        <td class="result-col">&nbsp;</td>
                        <td class="result-col">&nbsp;</td>
                        <td class="result-col">&nbsp;</td>
                        <td class="result-col">&nbsp;</td>
                        <td class="sign-col">&nbsp;</td>
                    </tr>
                <?php endforeach; ?>

                <!-- แถวว่างเพิ่มเติมสำหรับกรอกเพิ่มด้วยมือหน้างาน -->
                <?php for ($extra = 0; $extra < 4; $extra++): ?>
                    <tr>
                        <td class="no-col">&nbsp;</td>
                        <td class="item-col">&nbsp;</td>
                        <td class="method-col">&nbsp;</td>
                        <td class="remark-col">&nbsp;</td>
                        <td class="result-col">&nbsp;</td>
                        <td class="result-col">&nbsp;</td>
                        <td class="result-col">&nbsp;</td>
                        <td class="result-col">&nbsp;</td>
                        <td class="sign-col">&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- ===== รายละเอียดการซ่อมแซม (ถ้ามี) ===== -->
        <table class="main-grid" style="margin-top:4px;">
            <thead>
                <tr>
                    <td class="section-row" colspan="9">รายละเอียดการซ่อมแซม / อะไหล่ที่ใช้เปลี่ยน (ถ้ามี)</td>
                </tr>
                <tr>
                    <th style="width:auto;">รายละเอียด</th>
                    <th colspan="4"></th>
                    <th colspan="2" class="result-header">ผลการซ่อม</th>
                    <th colspan="1" class="result-header">รายมือชื่อ</th>
                    <th style="width:0; padding:0; border:none;"></th>
                </tr>
            </thead>
            <tbody>
                <?php for ($r = 0; $r < 3; $r++): ?>
                    <tr>
                        <td class="dot-row" colspan="4">&nbsp;</td>
                        <td class="result-col">&nbsp;</td>
                        <td class="result-col">&nbsp;</td>
                        <td class="sign-col" colspan="2">&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- ===== Footer ===== -->
        <table class="footer-table">
            <tr>
                <td style="width:50%;">Control Copy Log Out: IT ..........................</td>
                <td style="width:50%; text-align:right;">
                    Form No: IT-F-003 &nbsp; Rev.00 &nbsp; Issue date: <?= date('d-m-Y') ?>
                </td>
            </tr>
        </table>

    </div>
</body>

</html>
