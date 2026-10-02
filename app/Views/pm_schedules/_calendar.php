<?php
$calendarStatuses = ['overdue'=>'เกินกำหนด', 'soon'=>'ใกล้ครบกำหนด', 'normal'=>'ปกติ'];
$canEdit = in_array($currentUser['user_role'], ['admin','manager'], true);
?>
<section class="pm-calendar" aria-labelledby="pm-month-title">
    <div class="pm-calendar-toolbar">
        <div><h2 id="pm-month-title"><?= $calendar['label'] ?></h2><span class="text-muted">ครบกำหนด <?= number_format($calendar['count']) ?> แผนในเดือนนี้</span></div>
        <div class="pm-calendar-controls">
            <?php foreach (['previous'=>'เดือนก่อนหน้า','next'=>'เดือนถัดไป'] as $direction=>$label): ?>
                <?php if ($calendar[$direction]): ?><a class="btn btn-outline-secondary" aria-label="<?= $label ?>" href="<?= htmlspecialchars(PmCalendar::url('calendar',$calendar[$direction],Search::term())) ?>"><?= $direction === 'previous' ? '‹' : '›' ?></a><?php endif; ?>
            <?php endforeach; ?>
            <a class="btn btn-outline-primary" href="<?= htmlspecialchars(PmCalendar::url('calendar',$calendar['current'],Search::term())) ?>">เดือนนี้</a>
            <form method="GET" action="<?= APP_URL ?>/pm-schedules" class="pm-month-picker">
                <input type="hidden" name="view" value="calendar">
                <input type="hidden" name="search" value="<?= htmlspecialchars(Search::term()) ?>">
                <label for="pm-month" class="visually-hidden">เลือกเดือน</label>
                <input class="form-control" type="month" name="month" id="pm-month" value="<?= $calendar['month'] ?>" min="1900-01" max="2100-12" required>
                <button class="btn btn-primary" type="submit">แสดง</button>
            </form>
        </div>
    </div>
    <div class="pm-calendar-legend"><span class="pm-status overdue">เกินกำหนด</span><span class="pm-status soon">ใกล้ครบกำหนด</span><span class="pm-status normal">ปกติ</span><span>ปีถัดไปเป็นวันคาดการณ์ตามรอบ PM จนกว่าจะบันทึกงานปีนี้</span></div>
    <?php if (!$calendar['count']): ?><p class="pm-calendar-empty" role="status">ไม่มีแผนที่ครบกำหนดในเดือนนี้<?= Search::term() !== '' ? 'ตามคำค้นที่เลือก' : '' ?> ลองเปลี่ยนเดือนหรือดูตารางทั้งหมด</p><?php endif; ?>
    <div class="pm-calendar-scroll" tabindex="0" role="region" aria-label="ปฏิทินรายเดือน เลื่อนแนวนอนเพื่อดูวันทั้งหมด">
        <div class="pm-calendar-grid">
            <?php foreach (['จันทร์','อังคาร','พุธ','พฤหัสบดี','ศุกร์','เสาร์','อาทิตย์'] as $weekday): ?><div class="pm-weekday"><?= $weekday ?></div><?php endforeach; ?>
            <?php foreach ($calendar['days'] as $day): ?>
                <section class="pm-calendar-day <?= !$day['inMonth'] ? 'outside' : '' ?> <?= $day['today'] ? 'is-today' : '' ?>" aria-label="<?= $day['date']->format('d/m/Y') ?>">
                    <time class="pm-day-number" datetime="<?= $day['date']->format('Y-m-d') ?>" <?= $day['today'] ? 'aria-current="date"' : '' ?>><?= $day['date']->format('j') ?></time>
                    <?php foreach ($day['events'] as $event): $status = isset($calendarStatuses[$event['pm_status']]) ? $event['pm_status'] : 'normal'; ?>
                        <article class="pm-calendar-event <?= $status ?>">
                            <span class="pm-event-status"><?= !empty($event['calendar_projection']) ? 'แผนปีถัดไป (คาดการณ์)' : $calendarStatuses[$status] ?></span>
                            <?php if (!empty($event['calendar_projection'])): ?>
                                <span class="pm-event-title"><?= htmlspecialchars($event['pm_title']) ?></span>
                            <?php else: ?>
                                <a class="pm-event-title" href="<?= APP_URL ?>/pm-schedules/<?= (int)$event['pm_schedule_id'] ?>/record"><?= htmlspecialchars($event['pm_title']) ?></a>
                            <?php endif; ?>
                            <!-- <span><?= htmlspecialchars($event['svp_device_name']) ?></span> -->
                            <!-- <small><?= htmlspecialchars(trim(($event['first_name'] ?? '') . ' ' . ($event['last_name'] ?? '')) ?: 'ไม่ระบุผู้รับผิดชอบ') ?></small> -->
                            <?php if ($canEdit): ?><a class="pm-event-edit" href="<?= APP_URL ?>/pm-schedules/<?= (int)$event['pm_schedule_id'] ?>/edit">แก้ไขแผน</a><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</section>
