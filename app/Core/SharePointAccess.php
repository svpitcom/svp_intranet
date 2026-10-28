<?php
final class SharePointAccess
{
    // Stable Intranet department IDs, not user-submitted names or folder IDs.
    // Shared folders: Operation/Marketing and Purchase/Purchase Raw Material.
    public const DEPARTMENTS = [
        1=>'PRODUCTION', 2=>'IT', 3=>'HR', 4=>'ACCOUNT', 5=>'TECHNICAL',
        6=>'MAINTENANCE', 7=>'PURCHASE', 8=>'OPERATION_MARKETING',
        9=>'OPERATION_MARKETING', 10=>'RAW_MATERIAL', 11=>'QC',
        12=>'PURCHASE', 13=>'DCC', 14=>'SAFETY', 15=>'ENVIRONMENT',
    ];
    public const LABELS = [
        'PRODUCTION'=>'Production', 'IT'=>'IT', 'HR'=>'Human Resource',
        'ACCOUNT'=>'Account & Financial', 'TECHNICAL'=>'Technical',
        'MAINTENANCE'=>'Maintenance', 'PURCHASE'=>'Purchase / Purchase Raw Material',
        'OPERATION_MARKETING'=>'Operation / Marketing', 'RAW_MATERIAL'=>'Raw Material',
        'QC'=>'Quality', 'DCC'=>'DCC', 'SAFETY'=>'Safety', 'ENVIRONMENT'=>'Environment',
    ];
    public static function allows(?array $user): bool { return $user!==null && !empty($user['is_active']); }
    public static function admin(?array $user): bool { return self::allows($user) && ($user['user_role']??'')==='admin'; }
    public static function department(?array $user): ?string
    {
        if (!self::allows($user)) return null;
        return self::DEPARTMENTS[(int)($user['svp_department_id']??0)]??null;
    }
}
