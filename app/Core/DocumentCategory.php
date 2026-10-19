<?php
final class DocumentCategory
{
    public const LABELS = [
        'MR' => '1.MR',
        'DC' => '2.DC',
        'WL' => '3.WL',
        'DL' => '4.DL',
        'MN' => '5.MN',
        'QC' => '6.QC',
        'RM' => '7.RM',
        'PU' => '8.PU',
        'OP' => '9.OP',
        'IT' => '10.IT',
        'ST' => '11.ST',
        'HR' => '12.HR',
        'EN' => '13.EN',
        'SF' => '14.SF',
        'SU' => '15.SU',
        'TC' => '16.TC',
        'AC' => '17.AC',
        'RECORDS' => '19.รายการรายชื่อบันทึก',
        'DESTRUCTION' => '20.บันทึกขอทำลายเอกสาร',
        'REVIEW' => '21.รายการทบทวนเอกสารประจำปี',
        'PR_DC' => 'PR DC',
        'AUDIT_EUDR' => 'ไฟล์สแกนที่เตรียม Audit EUDR ทั้ง 8 ชุด',
    ];

    public static function infer(array $row): string
    {
        if (preg_match('/^ชีต: ([A-Z]+) แถว:/mu', $row['notes'] ?? '', $m)) {
            return isset(self::LABELS[$m[1]]) ? $m[1] : '';
        }
        if (preg_match('/^([A-Z]+)-/', $row['code'] ?? '', $m) && isset(self::LABELS[$m[1]])) return $m[1];
        return '';
    }

    public static function folder(SharePointClient $client, string $category): string
    {
        if (!isset(self::LABELS[$category]) && $category !== 'MASTER') throw new InvalidArgumentException('ไม่พบหมวดเอกสาร');
        $find = static function (string $parent, string $name) use ($client): string {
            $cursor = '';
            $seen = [];
            do {
                $page = $client->dccFiles($parent, $cursor);
                foreach ($page['items'] as $item) if (isset($item['folder']) && $item['name'] === $name) return $item['id'];
                $cursor = $page['cursor'];
                if ($cursor !== '' && isset($seen[$cursor])) throw new RuntimeException('ไม่สามารถอ่านหน้าถัดไป');
                $seen[$cursor] = true;
            } while ($cursor !== '');
            throw new RuntimeException('ไม่พบโฟลเดอร์ ' . $name . ' ใน SharePoint DCC');
        };
        $root = $find('', '0. Document control');
        return $find($root, $category === 'MASTER' ? '0.รวมทะเบียนเอกสาร' : self::LABELS[$category]);
    }
}
