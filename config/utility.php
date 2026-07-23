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
    | Zone Centers
    |--------------------------------------------------------------------
    | Approximate [lat, lng] center for each configured zone above, kept
    | within service_area_bounds. No real GIS boundary data exists for
    | these zones — this is just an anchor point used to center the
    | network map on a zone, place a zone-wide outage marker, and pick
    | the nearest zone to a customer's live location on the incident
    | report forms. Keep this list's keys in sync with "zones" above.
    */

    "zone_centers" => [
        "Njoro" => [-0.333, 35.942],
        "Nakuru Town" => [-0.303, 36.080],
        "Egerton" => [-0.370, 35.930],
        "Lanet" => [-0.233, 36.150],
        "Molo" => [-0.250, 35.870],
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
    | Photo Storage Disk
    |--------------------------------------------------------------------
    | Which filesystem disk (config/filesystems.php) photos are stored
    | on and served from. Defaults to the app-wide default disk so a
    | single FILESYSTEM_DISK=s3 switch moves photos too, but can be set
    | independently via PHOTO_DISK — e.g. to keep everything else local
    | while only offloading photos to S3, needed once the app runs on
    | more than one server (local disk isn't shared between them).
    | Never point this at the "public" disk — photos are only ever
    | served through the authorized /photos/{photo} route.
    */

    "photo_disk" => env("PHOTO_DISK", env("FILESYSTEM_DISK", "local")),

    /*
    |--------------------------------------------------------------------
    | Escalation Threshold
    |--------------------------------------------------------------------
    | A work order still awaiting technician claim, or a complaint still
    | awaiting admin review, longer than this many days is flagged as
    | stalled on the management dashboard.
    */

    "stall_threshold_days" => (int) env("UTILITY_STALL_THRESHOLD_DAYS", 3),

    /*
    |--------------------------------------------------------------------
    | Submission Rate Limiting
    |--------------------------------------------------------------------
    | Caps how many leak reports, service requests, or complaints a
    | single customer can submit within the given window, to keep the
    | technician dispatch queue from being flooded by one account.
    */

    "submission_rate_limit" => (int) env("UTILITY_SUBMISSION_RATE_LIMIT", 10),
    "submission_rate_window_minutes" => (int) env("UTILITY_SUBMISSION_RATE_WINDOW_MINUTES", 1),

    /*
    |--------------------------------------------------------------------
    | Payment Rate Limiting
    |--------------------------------------------------------------------
    | Caps how often a customer can initiate a gateway payment attempt
    | or poll its status — each call can trigger a real outbound
    | request to M-Pesa/Pesapal, so this isn't just abuse prevention,
    | it protects against hammering the gateways themselves.
    */

    "payments_rate_limit" => (int) env("UTILITY_PAYMENTS_RATE_LIMIT", 20),
    "payments_rate_window_minutes" => (int) env("UTILITY_PAYMENTS_RATE_WINDOW_MINUTES", 1),

    /*
    |--------------------------------------------------------------------
    | AI Chat Assistant
    |--------------------------------------------------------------------
    | "Ask Aquameter" — the customer-facing chat assistant. Model is
    | intentionally a cheap/fast one since it only explains data handed
    | to it in the system prompt, not complex reasoning. Rate limited
    | the same way submissions are, since every message costs real money.
    */

    "ai_chat_model" => env("OPENAI_CHAT_MODEL", "gpt-4o-mini"),
    "ai_chat_rate_limit" => (int) env("UTILITY_AI_CHAT_RATE_LIMIT", 20),
    "ai_chat_rate_window_minutes" => (int) env("UTILITY_AI_CHAT_RATE_WINDOW_MINUTES", 1),
];
