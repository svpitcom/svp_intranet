<?php
class Search
{
    public static function term(): string
    {
        return is_string($_GET['search'] ?? null) ? trim($_GET['search']) : '';
    }

    public static function suffix(): string
    {
        return '&search=' . rawurlencode(self::term());
    }

    /** Search the complete unpaginated list, using only explicitly listed display fields. */
    public static function rows(array $rows, string $term, array $fields): array
    {
        $tokens = preg_split('/\s+/u', trim($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (!$tokens) return $rows;
        $labels = ['completed'=>'ปกติดี', 'partial'=>'ทำได้บางส่วน', 'issue_found'=>'พบปัญหา', 'overdue'=>'เกินกำหนด', 'soon'=>'ใกล้ครบกำหนด', 'normal'=>'ปกติ'];
        return array_values(array_filter($rows, static function ($row) use ($tokens, $fields, $labels) {
            $values = [];
            foreach ($fields as $field) {
                $value = (string) ($row[$field] ?? '');
                $values[] = $value;
                if (in_array($field, ['result_status', 'pm_status'], true)) $values[] = $labels[$value] ?? '';
            }
            $text = implode(' ', $values);
            foreach ($tokens as $token) if (mb_stripos($text, $token) === false) return false;
            return true;
        }));
    }
}
