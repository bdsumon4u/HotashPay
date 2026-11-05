<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cooling-Off Verification Window (Minutes)
    |--------------------------------------------------------------------------
    |
    | The safety window time to look for incoming SMS or TrxID confirmations.
    | Default is 60 minutes.
    |
    */
    'window_time_minutes' => (int) env('PAYMENT_WINDOW_MINUTES', 60),

    'sms_match_window_minutes' => (int) env('PAYMENT_SMS_MATCH_WINDOW_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Verification Timeout (Minutes)
    |--------------------------------------------------------------------------
    |
    | Maximum duration for an asynchronous verification job before it is marked
    | as failed and the customer is prompted to contact support.
    |
    */
    'timeout_minutes' => (int) env('PAYMENT_TIMEOUT_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Maximum Wait Time For Incoming SMS (Minutes)
    |--------------------------------------------------------------------------
    |
    | How long to keep a pending submission waiting for an incoming Telegram SMS
    | before moving it to manual review status.
    |
    */
    'max_wait_time_minutes' => (int) env('PAYMENT_MAX_WAIT_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Auto-Approve Enabled
    |--------------------------------------------------------------------------
    |
    | When enabled, transactions meeting all verification criteria without any
    | conflicts or suspicion will be approved automatically.
    |
    */
    'auto_approve' => (bool) env('PAYMENT_AUTO_APPROVE', true),

    /*
    |--------------------------------------------------------------------------
    | AI Confidence Threshold
    |--------------------------------------------------------------------------
    |
    | Minimum confidence score (0.0 to 1.0) returned by the AI model to allow
    | auto-approval.
    |
    */
    'confidence_threshold' => (float) env('PAYMENT_CONFIDENCE_THRESHOLD', 0.85),

    /*
    |--------------------------------------------------------------------------
    | Screenshot Retention Periods
    |--------------------------------------------------------------------------
    |
    | Days to retain verified receipts (for dispute & audit resolution), and
    | hours to retain failed/timed-out screenshots (for customer support).
    |
    */
    'screenshot_retention_days' => (int) env('PAYMENT_SCREENSHOT_RETENTION_DAYS', 14),
    'failed_screenshot_retention_hours' => (int) env('PAYMENT_FAILED_SCREENSHOT_RETENTION_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | Google Gemini Vision Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials and model settings for screenshot multimodal analysis.
    | The Flash-Lite model provides low-cost multimodal OCR and extraction.
    |
    */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-flash-lite-latest'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],
];
