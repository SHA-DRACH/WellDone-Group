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

    /*
     * Google OAuth — "Continue with Google" on the sign-in and register
     * screens. Credentials come from Google Cloud Console → APIs & Services →
     * Credentials → OAuth 2.0 Client ID (type: Web application).
     *
     * The redirect URI registered there must match GOOGLE_REDIRECT_URI here
     * exactly, including scheme, port and trailing path.
     */
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],

    /*
     * EmailJS — used by the `emailjs` mailer (App\Mail\Transport\EmailJsTransport).
     *
     * The private key must never be exposed to the browser; it is only ever
     * read here, server-side. In the EmailJS dashboard, enable
     * Account → Security → "Allow EmailJS API for non-browser applications",
     * otherwise the REST endpoint rejects server-side calls.
     */
    'emailjs' => [
        'service_id' => env('EMAILJS_SERVICE_ID'),
        'template_id' => env('EMAILJS_TEMPLATE_ID'),
        'public_key' => env('EMAILJS_PUBLIC_KEY'),
        'private_key' => env('EMAILJS_PRIVATE_KEY'),
        'timeout' => env('EMAILJS_TIMEOUT', 30),
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

];
