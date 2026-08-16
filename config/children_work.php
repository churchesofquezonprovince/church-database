<?php

return [
    'google_sheets' => [
        'enabled' => env('CHILDREN_WORK_GOOGLE_SHEETS_ENABLED', false),
        'spreadsheet_id' => env('CHILDREN_WORK_GOOGLE_SHEETS_ID', '1gK3MV14hIKryReed5GPxNms6PtaZTFGszapQssAjAGI'),
        'sheet_name' => env('CHILDREN_WORK_GOOGLE_SHEETS_SHEET_NAME'),
        'credentials_path' => env('CHILDREN_WORK_GOOGLE_SHEETS_CREDENTIALS_PATH', 'storage/app/google-calendar/service-account.json'),
        'header_row' => env('CHILDREN_WORK_GOOGLE_SHEETS_HEADER_ROW', 1),
    ],
];
