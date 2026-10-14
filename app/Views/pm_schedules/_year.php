<?php
$yearUrl = static fn(int $year): string => APP_URL . '/pm-schedules?' . http_build_query(['view' => 'year', 'year' => $year, 'search' => Search::term()]);
$yearEscape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/pm-year.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/pm-year.css') ?>">
<section aria-labelledby="pm-year-title">
    <div class="pm-calendar-toolbar mb-3">
        <div><h2 id="pm-year-title">ปฏิทิน PM ปี <?= $annual['year'] + 543 ?></h2><p class="text-muted mb-0"><?= number_format($annual['count']) ?> รายการ รวมวันคาดการณ์ตามข้อมูลแผน<?= Search::term() !== '' ? 'ที่ตรงกับคำค้น' : '' ?></p></div>
        <form method="GET" action="<?= APP_URL ?>/pm-schedules" class="d-flex flex-wrap align-items-center gap-2">
            <input type="hidden" name="view" value="year"><input type="hidden" name="search" value="<?= $yearEscape(Search::term()) ?>">
            <?php if ($annual['year'] > 1900): ?><a class="btn btn-outline-secondary" href="<?= $yearEscape($yearUrl($annual['year'] - 1)) ?>" aria-label="ปีก่อนหน้า">‹</a><?php endif; ?>
            <label for="pm-year">ปี ค.ศ.</label><input class="form-control pm-year-input" id="pm-year" type="number" name="year" min="1900" max="2100" value="<?= $annual['year'] ?>" required>
            <button class="btn btn-primary" type="submit">แสดง</button>
            <?php if ($annual['year'] < 2100): ?><a class="btn btn-outline-secondary" href="<?= $yearEscape($yearUrl($annual['year'] + 1)) ?>" aria-label="ปีถัดไป">›</a><?php endif; ?>
            <a class="btn btn-outline-primary" href="<?= $yearEscape($yearUrl((int)(new DateTimeImmutable('now', new DateTimeZone('Asia/Bangkok')))->format('Y'))) ?>">ปีนี้</a>
        </form>
    </div>
    <p class="text-muted">วันที่พื้นสีน้ำเงินมีงาน PM · กดวันที่เพื่อดูรายละเอียดในปฏิทินรายเดือน · รวมวันคาดการณ์ที่แผนเดิมระบุไว้ ไม่สร้างงานซ้ำหรือประวัติย้อนหลัง</p>
    <?php if (!$annual['count']): ?><p class="alert alert-info">ไม่มีแผน PM หรือวันคาดการณ์ในปีที่เลือก<?= Search::term() !== '' ? 'ตามคำค้นนี้' : '' ?></p><?php endif; ?>
    <div class="pm-year-grid">
    <?php foreach ($annual['months'] as $month): ?>
        <section class="pm-year-month" aria-label="<?= $yearEscape($month['label']) ?>">
            <header><a href="<?= $yearEscape(PmCalendar::url('calendar', $month['month'], Search::term())) ?>"><?= $yearEscape($month['label']) ?></a><span><?= $month['count'] ?> รายการ</span></header>
            <div class="pm-year-days">
                <?php foreach (['จ','อ','พ','พฤ','ศ','ส','อา'] as $weekday): ?><span class="pm-year-weekday"><?= $weekday ?></span><?php endforeach; ?>
                <?php foreach ($month['days'] as $day): ?>
                    <?php if (!$day['inMonth']): ?><span aria-hidden="true"></span><?php else:
                        $dateLabel = $day['date']->format('d/m/Y');
                        $eventCount = count($day['events']);
                    ?>
                        <?php if ($eventCount): ?><a class="pm-year-day has-events <?= $day['today'] ? 'today' : '' ?>" href="<?= $yearEscape(PmCalendar::url('calendar', $month['month'], Search::term()) . '#pm-day-' . $day['date']->format('Y-m-d')) ?>" aria-label="<?= $dateLabel ?> มี <?= $eventCount ?> รายการ" title="<?= $dateLabel ?> · <?= $eventCount ?> รายการ"><span><?= $day['date']->format('j') ?></span><small><?= $eventCount ?> งาน</small></a>
                        <?php else: ?><span class="pm-year-day <?= $day['today'] ? 'today' : '' ?>" <?= $day['today'] ? 'aria-current="date"' : '' ?>><?= $day['date']->format('j') ?></span><?php endif; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
    </div>
</section>
