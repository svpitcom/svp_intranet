<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    
    <style>
        @font-face {
            font-family: 'Sarabun';
            src: url('<?= BASE_PATH ?>/public/assets/fonts/Sarabun-Regular.ttf') format('truetype');
        }

        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 13px;
            color: #222;
        }

        h2 {
            text-align: center;
            margin-bottom: 4px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
            font-size: 11px;
        }

        table.info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.info td {
            border: 1px solid #999;
            padding: 8px 10px;
            vertical-align: top;
        }

        table.info td.label {
            background-color: #f0f0f0;
            font-weight: bold;
            width: 180px;
        }

        .status-box {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            color: #fff;
            font-size: 11px;
        }

        .overdue {
            background-color: #dc3545;
        }

        .soon {
            background-color: #ffc107;
            color: #000;
        }

        .normal {
            background-color: #198754;
        }

        .checklist-box {
            border: 1px solid #999;
            padding: 12px;
            min-height: 100px;
            white-space: pre-line;
            margin-bottom: 20px;
        }

        .sign-table {
            width: 100%;
            margin-top: 40px;
        }

        .sign-table td {
            width: 50%;
            text-align: center;
            padding-top: 50px;
        }

        .sign-line {
            border-top: 1px solid #333;
            width: 80%;
            margin: 0 auto;
            padding-top: 4px;
        }

        .footer {
            margin-top: 25px;
            font-size: 10px;
            color: #666;
            text-align: right;
        }
    </style>
</head>

<body>
    <h2>แบบฟอร์มแผนบำรุงรักษาเชิงป้องกัน (PM)</h2>
    <p class="subtitle">SVP Intranet — Preventive Maintenance Form</p>

    <table class="info">
        <tr>
            <td class="label">ชื่อแผน PM</td>
            <td><?= htmlspecialchars($schedule['pm_title']) ?></td>
        </tr>
        <tr>
            <td class="label">อุปกรณ์</td>
            <td><?= htmlspecialchars($schedule['svp_device_name']) ?></td>
        </tr>
        <tr>
            <td class="label">รอบความถี่</td>
            <td>ทุก <?= (int) $schedule['frequency_days'] ?> วัน</td>
        </tr>
        <tr>
            <td class="label">ผู้รับผิดชอบ</td>
            <td><?= htmlspecialchars(trim(($schedule['first_name'] ?? '') . ' ' . ($schedule['last_name'] ?? '')) ?: '-') ?></td>
        </tr>
        <tr>
            <td class="label">ทำครั้งล่าสุด</td>
            <td><?= htmlspecialchars($schedule['last_pm_date'] ?? 'ยังไม่เคยทำ') ?></td>
        </tr>
        <tr>
            <td class="label">ครบกำหนดครั้งถัดไป</td>
            <td><?= htmlspecialchars($schedule['next_pm_date']) ?></td>
        </tr>
        <tr>
            <td class="label">สถานะ</td>
            <td>
                <?php
                $daysRemaining = (int) ((strtotime($schedule['next_pm_date']) - strtotime(date('Y-m-d'))) / 86400);
                $status = PmSchedule::statusFromDaysRemaining($daysRemaining);
                $labelMap = ['overdue' => 'เกินกำหนด', 'soon' => 'ใกล้ครบกำหนด', 'normal' => 'ปกติ'];
                ?>
                <span class="status-box <?= $status ?>"><?= $labelMap[$status] ?></span>
            </td>
        </tr>
    </table>

    <p><strong>รายการตรวจเช็ค (Checklist)</strong></p>
    <div class="checklist-box">
        <?= htmlspecialchars($schedule['checklist'] ?: '-') ?>
    </div>

    <table class="sign-table">
        <tr>
            <td>
                <div class="sign-line">ผู้ตรวจเช็ค / วันที่</div>
            </td>
            <td>
                <div class="sign-line">ผู้อนุมัติ / วันที่</div>
            </td>
        </tr>
    </table>

    <div class="footer">ออกเอกสารเมื่อ <?= date('d/m/Y H:i') ?> น. — SVP Intranet</div>
</body>

</html>