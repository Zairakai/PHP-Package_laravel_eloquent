<?php

declare(strict_types=1);

return [
    /*
    | -------------------------------------------------------------------------
    | Column Resolution Logging
    | -------------------------------------------------------------------------
    |
    | Configure logging behavior for column resolution. This helps track
    | deprecated column usage and missing column mappings during development.
    |
    */

    'logging' => [
        /*
        | -------------------------------------------------------------------------
        | Enable Logging
        | -------------------------------------------------------------------------
        |
        | When enabled, the package will log warnings for deprecated columns
        | (COLUMNS_DELETED) and info messages for missing columns (fallback).
        |
        | Set to false in production to disable all column resolution logging.
        |
        | Default: true (enabled for all environments)
        |
        */
        'enabled' => env('ELOQUENT_LOGGING_ENABLED', true),

        /*
        | -------------------------------------------------------------------------
        | Log Channel
        | -------------------------------------------------------------------------
        |
        | The log channel to use for column resolution messages.
        | Must be a valid channel defined in config/logging.php
        |
        | Common channels: 'stack', 'single', 'daily', 'stderr', 'null'
        |
        | Default: null (uses default Laravel log channel)
        |
        */
        'channel' => env('ELOQUENT_LOGGING_CHANNEL'),

        /*
        | -------------------------------------------------------------------------
        | Log Levels
        | -------------------------------------------------------------------------
        |
        | Configure log levels for different types of column resolution events.
        |
        | Available levels: 'emergency', 'alert', 'critical', 'error',
        |                   'warning', 'notice', 'info', 'debug'
        |
        */
        'levels' => [
            /*
            | Log level for deprecated column access (COLUMNS_DELETED redirect)
            |
            | This indicates legacy code that should be updated to use new column names.
            |
            | Default: 'warning'
            */
            'deprecated' => env('ELOQUENT_LOG_LEVEL_DEPRECATED', 'warning'),

            /*
            | Log level for missing column mappings (fallback to key)
            |
            | This indicates a column key that's not in COLUMNS constant.
            | Usually not critical as the code continues with fallback.
            |
            | Default: 'info'
            */
            'missing' => env('ELOQUENT_LOG_LEVEL_MISSING', 'info'),
        ],

        /*
        | -------------------------------------------------------------------------
        | Include Backtrace
        | -------------------------------------------------------------------------
        |
        | When enabled, log entries will include a backtrace showing where
        | the column resolution was called from. Helpful for debugging but
        | increases log size.
        |
        | Default: true (enabled)
        |
        */
        'include_backtrace' => env('ELOQUENT_LOGGING_BACKTRACE', true),

        /*
        | -------------------------------------------------------------------------
        | Backtrace Depth
        | -------------------------------------------------------------------------
        |
        | Number of stack frames to include in the backtrace (when enabled).
        | Higher values provide more context but increase log size.
        |
        | Default: 5
        |
        */
        'backtrace_depth' => env('ELOQUENT_LOGGING_BACKTRACE_DEPTH', 5),

        /*
        | -------------------------------------------------------------------------
        | Excluded Models
        | -------------------------------------------------------------------------
        |
        | List of fully qualified model class names that should NOT log
        | column resolution messages. Useful for noisy third-party models
        | or models where you intentionally use fallback behavior.
        |
        | Example:
        |   'excluded_models' => [
        |       App\Models\LegacyUser::class,
        |       App\Models\ThirdParty\ExternalModel::class,
        |   ],
        |
        | Default: [] (no models excluded)
        |
        */
        'excluded_models' => [],
    ],
];
