<?php

return [
    /*
    |--------------------------------------------------------------------
    | Account Number Prefix
    |--------------------------------------------------------------------
    | Used when generating unique customer account numbers.
    */

    "account_number_prefix" => env("UTILITY_ACCOUNT_PREFIX", "AQM"),

    /*
    |--------------------------------------------------------------------
    | Meter Number Prefix
    |--------------------------------------------------------------------
    | Used when generating unique meter numbers.
    */

    "meter_number_prefix" => env("UTILITY_METER_PREFIX", "MTR"),

    /*
    |--------------------------------------------------------------------
    | Service Zones
    |--------------------------------------------------------------------
    | The utility's coverage zones. Used for account assignment,
    | technician zone routing, and outage broadcasts. Add/remove
    | zones here only — never hardcode zone names elsewhere.
    */

    "zones" => [
        "Njoro",
        "Nakuru Town",
        "Egerton",
        "Lanet",
        "Molo",
    ],

    /*
    |--------------------------------------------------------------------
    | Meter Anomaly Multiplier
    |--------------------------------------------------------------------
    | A reading is flagged anomalous if its consumption delta exceeds
    | this multiple of the meter''s historical average consumption.
    */

    "anomaly_multiplier" => (float) env("UTILITY_ANOMALY_MULTIPLIER", 3.0),

    /*
    |--------------------------------------------------------------------
    | Billing
    |--------------------------------------------------------------------
    | Flat rate per unit of water consumed, and how many days a
    | customer has to pay before a bill is considered overdue.
    */

    "rate_per_unit" => (float) env("UTILITY_RATE_PER_UNIT", 150.0),
    "bill_due_days" => (int) env("UTILITY_BILL_DUE_DAYS", 7),

    /*
    |--------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------
    | Accepted payment recording methods. Add/remove here only.
    */

    "payment_methods" => [
        "cash",
        "mpesa",
        "bank_transfer",
    ],
];
