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
            ],
            'childrens_meeting' => [
                'name' => "Children's Meeting in Quezon",
                'id' => env('GOOGLE_CALENDAR_CHILDRENS_MEETING_ID'),
            ],
            'international_activities' => [
                'name' => 'International Activities',
                'id' => env('GOOGLE_CALENDAR_INTERNATIONAL_ACTIVITIES_ID'),
            ],
            'local_activities' => [
                'name' => 'Local Activities',
                'id' => env('GOOGLE_CALENDAR_LOCAL_ACTIVITIES_ID'),
            ],
            'national_activities' => [
                'name' => 'National Activities',
                'id' => env('GOOGLE_CALENDAR_NATIONAL_ACTIVITIES_ID'),
            ],
            'provincial_activities' => [
                'name' => 'Provincial Activities',
                'id' => env('GOOGLE_CALENDAR_PROVINCIAL_ACTIVITIES_ID'),
            ],
            'regional_activities' => [
                'name' => 'Regional Activities',
                'id' => env('GOOGLE_CALENDAR_REGIONAL_ACTIVITIES_ID'),
            ],
            'regular_weekly_meetings' => [
                'name' => 'Regular Weekly Meetings',
                'id' => env('GOOGLE_CALENDAR_REGULAR_WEEKLY_MEETINGS_ID'),
            ],
        ],

        'credentials_path' => env('GOOGLE_CALENDAR_CREDENTIALS_PATH', 'storage/app/google-calendar/service-account.json'),
    ],

];
