<?php
return [
    'tenant_id' => env('SHAREPOINT_TENANT_ID', ''),
    'client_id' => env('SHAREPOINT_CLIENT_ID', ''),
    'client_secret' => env('SHAREPOINT_CLIENT_SECRET', ''),
    'site_id' => env('SHAREPOINT_SITE_ID', ''),
    'drive_id' => env('SHAREPOINT_DRIVE_ID', ''),
    'export_folder_id' => env('SHAREPOINT_EXPORT_FOLDER_ID', ''),
    'department_folders' => array_filter([
        'ACCOUNT' => env('SHAREPOINT_FOLDER_ACCOUNT_ID', ''),
        'DCC' => env('SHAREPOINT_FOLDER_DCC_ID', ''),
        'ENVIRONMENT' => env('SHAREPOINT_FOLDER_ENVIRONMENT_ID', ''),
        'HR' => env('SHAREPOINT_FOLDER_HR_ID', ''),
        'IT' => env('SHAREPOINT_FOLDER_IT_ID', ''),
        'MAINTENANCE' => env('SHAREPOINT_FOLDER_MAINTENANCE_ID', ''),
        'OPERATION_MARKETING' => env('SHAREPOINT_FOLDER_OPERATION_MARKETING_ID', ''),
        'PRODUCTION' => env('SHAREPOINT_FOLDER_PRODUCTION_ID', ''),
        'QC' => env('SHAREPOINT_FOLDER_QC_ID', ''),
        'SUSTAINABILITY' => env('SHAREPOINT_FOLDER_SUSTAINABILITY_ID', ''),
    ], static fn($id) => trim((string)$id) !== ''),
];
