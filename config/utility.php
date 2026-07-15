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

    /*
    |--------------------------------------------------------------------
    | Disconnection Flow
    |--------------------------------------------------------------------
    | Grace period (in days, after a bill's due date) before an overdue
    | bill is considered defaulted. Notice period (in days) a customer
    | has after a disconnection notice is issued before an admin may
    | sign off on dispatching the disconnection to a technician.
    */

    "default_grace_days" => (int) env("UTILITY_DEFAULT_GRACE_DAYS", 14),
    "disconnection_notice_days" => (int) env("UTILITY_DISCONNECTION_NOTICE_DAYS", 7),

    /*
    |--------------------------------------------------------------------
    | Service Request Types
    |--------------------------------------------------------------------
    | Categories a customer may pick when filing a service request.
    | Add/remove here only — never hardcode a type elsewhere.
    */

    "service_request_types" => [
        "meter_inspection",
        "pipe_maintenance",
        "meter_relocation",
        "other",
    ],

    /*
    |--------------------------------------------------------------------
    | Service Area Bounds
    |--------------------------------------------------------------------
    | Plausible lat/lng bounds for this utility's coverage area, covering
    | Nakuru County (where the configured zones above are located). Used
    | to reject geolocation coordinates on a leak report that fall well
    | outside where this utility could plausibly operate.
    */

    "service_area_bounds" => [
        "min_lat" => (float) env("UTILITY_SERVICE_MIN_LAT", -0.55),
        "max_lat" => (float) env("UTILITY_SERVICE_MAX_LAT", -0.05),
        "min_lng" => (float) env("UTILITY_SERVICE_MIN_LNG", 35.85),
        "max_lng" => (float) env("UTILITY_SERVICE_MAX_LNG", 36.25),
    ],

    /*
    |--------------------------------------------------------------------
    | Photo Uploads
    |--------------------------------------------------------------------
    | Evidence photos on leak reports, service requests, and completed
    | work orders. Stored on the private disk, served only through an
    | authorized route — never a public URL.
    */

    "max_photos_per_upload" => (int) env("UTILITY_MAX_PHOTOS_PER_UPLOAD", 3),
    "max_photo_size_kb" => (int) env("UTILITY_MAX_PHOTO_SIZE_KB", 2048),
    "photo_mimes" => ["jpeg", "jpg", "png", "webp"],

    /*
    |--------------------------------------------------------------------
    | Escalation Threshold
    |--------------------------------------------------------------------
    | A work order still awaiting technician claim, or a complaint still
    | awaiting admin review, longer than this many days is flagged as
    | stalled on the management dashboard.
    */

    "stall_threshold_days" => (int) env("UTILITY_STALL_THRESHOLD_DAYS", 3),
];
