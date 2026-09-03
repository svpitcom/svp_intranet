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
            font-size: 12px;
        }

        h2 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background-color: #333;
            color: #fff;
        }

        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            color: #fff;
            font-size: 10px;
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

        .footer {
            margin-top: 20px;
            font-size: 10px;
            color: #666;
            text-align: right;
        }
    </style>
</head>

<body>
    <h2>รายงานแผนบำรุงรักษาเชิงป้องกัน (PM Schedule)</h2>
    <p>วันที่ออกรายงาน: <?= date('d/m/Y H:i') ?> น.</p>

    <table>
        <thead>
            <tr>
                <th>Hardware</th>
                <th>ชื่อแผน PM</th>
                <th>รอบ (วัน)</th>
                <th>ผู้รับผิดชอบ</th>
                <th>ครบกำหนดครั้งถัดไป</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($schedules as $s): ?>
                <?php
                $labelMap = ['overdue' => 'เกินกำหนด', 'soon' => 'ใกล้ครบกำหนด', 'normal' => 'ปกติ'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($s['svp_device_name']) ?></td>
                    <td><?= htmlspecialchars($s['pm_title']) ?></td>
                    <td>ทุก <?= (int) $s['frequency_days'] ?> วัน</td>
                    <td><?= htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')) ?: '-') ?></td>
                    <td><?= htmlspecialchars($s['next_pm_date']) ?></td>
                    <td><span class="badge <?= $s['pm_status'] ?>"><?= $labelMap[$s['pm_status']] ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">SVP Intranet — ระบบบริหารจัดการบำรุงรักษาเชิงป้องกัน</div>
</body>

</html>