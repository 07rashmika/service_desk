<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SLA alerts
    |--------------------------------------------------------------------------
    |
    | How many minutes before a ticket's deadline the assigned technician gets a
    | "due soon" alert. The overdue alert is sent once the deadline has passed.
    |
    */

    'sla_warning_minutes' => (int) env('SLA_WARNING_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Auto-close
    |--------------------------------------------------------------------------
    |
    | Resolved tickets the requester neither confirms nor reopens are closed
    | automatically after this many days.
    |
    */

    'auto_close_after_days' => (int) env('AUTO_CLOSE_AFTER_DAYS', 3),

];
