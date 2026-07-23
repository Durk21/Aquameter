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

    "postmark" => [
        "key" => env("POSTMARK_API_KEY"),
    ],

    "resend" => [
        "key" => env("RESEND_API_KEY"),
    ],

    "ses" => [
        "key" => env("AWS_ACCESS_KEY_ID"),
        "secret" => env("AWS_SECRET_ACCESS_KEY"),
        "region" => env("AWS_DEFAULT_REGION", "us-east-1"),
    ],

    "slack" => [
        "notifications" => [
            "bot_user_oauth_token" => env("SLACK_BOT_USER_OAUTH_TOKEN"),
            "channel" => env("SLACK_BOT_USER_DEFAULT_CHANNEL"),
        ],
    ],

    "browsershot" => [
        "node_binary" => env("NODE_BINARY_PATH"),
        // Chromium's sandbox needs a privilege this container/runner
        // doesn't have (unprivileged user namespaces). Only ever
        // enable this where the outer environment is already
        // isolated and throwaway, e.g. CI — never in production.
        "no_sandbox" => (bool) env("BROWSERSHOT_NO_SANDBOX", false),
    ],

    /*
    |--------------------------------------------------------------------------
    | M-Pesa (Safaricom Daraja)
    |--------------------------------------------------------------------------
    | Powers the customer-facing STK Push payment option. Get sandbox
    | credentials at developer.safaricom.co.ke. callback_url must be a
    | publicly reachable URL (e.g. via ngrok in local dev) pointing at
    | POST /webhooks/mpesa/callback — Safaricom calls it server-to-server.
    */

    "mpesa" => [
        "consumer_key" => env("MPESA_CONSUMER_KEY"),
        "consumer_secret" => env("MPESA_CONSUMER_SECRET"),
        "shortcode" => env("MPESA_SHORTCODE"),
        "passkey" => env("MPESA_PASSKEY"),
        "env" => env("MPESA_ENV", "sandbox"),
        "callback_url" => env("MPESA_CALLBACK_URL"),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pesapal
    |--------------------------------------------------------------------------
    | Powers the customer-facing bank/card payment option — a Kenya-
    | founded payment aggregator covering major bank transfers and
    | cards through one API. Get sandbox credentials at
    | developer.pesapal.com. callback_url must be publicly reachable
    | for IPN delivery, same requirement as the M-Pesa callback above.
    */

    "pesapal" => [
        "consumer_key" => env("PESAPAL_CONSUMER_KEY"),
        "consumer_secret" => env("PESAPAL_CONSUMER_SECRET"),
        "env" => env("PESAPAL_ENV", "sandbox"),
        "callback_url" => env("PESAPAL_CALLBACK_URL"),
    ],

];
