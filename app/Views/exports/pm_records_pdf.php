<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
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
    </style>
</head>

<body>
    <h2>รายงานประวัติการทำ PM</h2>
    <p>วันที่ออกรายงาน: <?= date('d/m/Y H:i') ?> น.</p>

    <table>
        <thead>
            <tr>
                <th>วันที่ทำ</th>
                <th>อุปกรณ์</th>
                <th>แผน PM</th>
                <th>ผู้ทำ</th>
                <th>ผลตรวจเช็ค</th>
                <th>หมายเหตุ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($records as $r): ?>
                <?php $statusLabel = ['completed' => 'ปกติดี', 'partial' => 'ทำได้บางส่วน', 'issue_found' => 'พบปัญหา'][$r['result_status']]; ?>
                <tr>
                    <td><?= htmlspecialchars($r['performed_date']) ?></td>
                    <td><?= htmlspecialchars($r['svp_device_name']) ?></td>
                    <td><?= htmlspecialchars($r['pm_title']) ?></td>
                    <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                    <td><?= $statusLabel ?></td>
                    <td><?= htmlspecialchars($r['notes'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>