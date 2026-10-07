<?php
class DocumentControlInput
{
    public static function validate(array $data): array
    {
        $errors=[];
        foreach (['code'=>80,'title'=>255,'revision'=>30] as $key=>$limit) {
            if (trim($data[$key]??'')==='' || mb_strlen($data[$key])>$limit) $errors[]='กรุณากรอกรหัสเอกสาร ชื่อ และ Revision ให้ครบ (ไม่เกิน 80, 255 และ 30 ตัวอักษร)';
        }
        if (!isset(ControlledDocument::STATUSES[$data['status']??''])) $errors[]='สถานะเอกสารไม่ถูกต้อง';
        foreach (['effective_date','review_date'] as $key) if (!empty($data[$key]) && !Validation::date($data[$key])) $errors[]='รูปแบบวันที่ไม่ถูกต้อง';
        if (!empty($data['effective_date']) && !empty($data['review_date']) && $data['review_date']<$data['effective_date']) $errors[]='วันทบทวนต้องไม่ก่อนวันที่มีผล';
        if (($data['status']??'')==='active' && (empty($data['effective_date']) || empty($data['file_item_id']))) $errors[]='เอกสารใช้งานต้องมีวันที่มีผลและไฟล์จาก DCC';
        if (mb_strlen($data['notes']??'')>5000) $errors[]='หมายเหตุต้องไม่เกิน 5,000 ตัวอักษร';
        return array_unique($errors);
    }

    public static function safeUrl(string $url): bool
    {
        return filter_var($url,FILTER_VALIDATE_URL) && strtolower(parse_url($url,PHP_URL_SCHEME)??'')==='https' && !parse_url($url,PHP_URL_USER) && !parse_url($url,PHP_URL_PASS);
    }
}
