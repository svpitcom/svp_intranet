<?php
class PmCalendar
{
    public static function build(array $schedules, $month, ?DateTimeImmutable $today = null): array
    {
        $today = $today ?? new DateTimeImmutable('today');
        $valid = is_string($month) && preg_match('/\A(19\d{2}|20\d{2}|2100)-(0[1-9]|1[0-2])\z/', $month);
        $first = new DateTimeImmutable(($valid ? $month : $today->format('Y-m')) . '-01');
        $start = $first->modify('-' . ((int) $first->format('N') - 1) . ' days');
        $last = $first->modify('last day of this month');
        $end = $last->modify('+' . (7 - (int) $last->format('N')) . ' days');
        $byDate = [];
        foreach ($schedules as $schedule) {
            $date = $schedule['next_pm_date'] ?? '';
            if (is_string($date) && Validation::date($date)) $byDate[$date][] = $schedule;
        }
        $days = [];
        $count = 0;
        for ($day = $start; $day <= $end; $day = $day->modify('+1 day')) {
            $inMonth = $day->format('Y-m') === $first->format('Y-m');
            $events = $inMonth ? ($byDate[$day->format('Y-m-d')] ?? []) : [];
            $count += count($events);
            $days[] = ['date' => $day, 'inMonth' => $inMonth, 'today' => $day->format('Y-m-d') === $today->format('Y-m-d'), 'events' => $events];
        }
        $names = [1=>'มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
        return ['month'=>$first->format('Y-m'), 'label'=>$names[(int)$first->format('n')] . ' ' . ((int)$first->format('Y') + 543), 'days'=>$days, 'count'=>$count,
            'previous'=>$first->format('Y-m') > '1900-01' ? $first->modify('-1 month')->format('Y-m') : null,
            'next'=>$first->format('Y-m') < '2100-12' ? $first->modify('+1 month')->format('Y-m') : null,
            'current'=>$today->format('Y-m')];
    }

    public static function url(string $view, string $month, string $search): string
    {
        return APP_URL . '/pm-schedules?' . http_build_query(['view'=>$view, 'month'=>$month, 'search'=>$search]);
    }
}
