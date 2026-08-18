<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

'immich' => [
    'url' => env('IMMICH_URL'),
    'api_key' => env('IMMICH_API_KEY'),
],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],


    'google_calendar' => [
        'enabled' => env('GOOGLE_CALENDAR_ENABLED', false),

        /*
         * Legacy single-calendar fallback.
         */
        'calendar_id' => env('GOOGLE_CALENDAR_ID'),

        /*
         * Multiple Google Calendars for COQP schedules.
         */
        'calendars' => [
            'quezon_province' => [
                'name' => 'Quezon Province',
                'id' => env('GOOGLE_CALENDAR_QUEZON_PROVINCE_ID'),
                'color' => '#009688',
            ],
            'childrens_meeting' => [
                'name' => "Children's Meeting in Quezon",
                'id' => env('GOOGLE_CALENDAR_CHILDRENS_MEETING_ID'),
                'color' => '#795548',
            ],
            'international_activities' => [
                'name' => 'International Activities',
                'id' => env('GOOGLE_CALENDAR_INTERNATIONAL_ACTIVITIES_ID'),
                'color' => '#B39D00',
            ],
            'local_activities' => [
                'name' => 'Local Activities',
                'id' => env('GOOGLE_CALENDAR_LOCAL_ACTIVITIES_ID'),
                'color' => '#AD4E6E',
            ],
            'national_activities' => [
                'name' => 'National Activities',
                'id' => env('GOOGLE_CALENDAR_NATIONAL_ACTIVITIES_ID'),
                'color' => '#6D4AFF',
            ],
            'provincial_activities' => [
                'name' => 'Provincial Activities',
                'id' => env('GOOGLE_CALENDAR_PROVINCIAL_ACTIVITIES_ID'),
                'color' => '#0B8DB3',
            ],
            'regional_activities' => [
                'name' => 'Regional Activities',
                'id' => env('GOOGLE_CALENDAR_REGIONAL_ACTIVITIES_ID'),
                'color' => '#00A896',
            ],
            'regular_weekly_meetings' => [
                'name' => 'Regular Weekly Meetings',
                'id' => env('GOOGLE_CALENDAR_REGULAR_WEEKLY_MEETINGS_ID'),
                'color' => '#8B5A2B',
            ],
        ],

        'credentials_path' => env('GOOGLE_CALENDAR_CREDENTIALS_PATH', 'storage/app/google-calendar/service-account.json'),
    ],

];
