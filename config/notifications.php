<?php

return [
    /*
    |--------------------------------------------------------------------
    | Notification Categories
    |--------------------------------------------------------------------
    | Every category a customer can toggle email delivery for, and the
    | label shown on their profile page. In-app (database) notifications
    | are never gated by this — only the mail channel. Add/remove a
    | category here only — never hardcode one elsewhere.
    */

    "categories" => [
        "bill_generated" => "New bills",
        "work_order_updates" => "Work order & service updates",
        "disconnection_notices" => "Disconnection notices",
        "complaint_updates" => "Complaint updates",
        "outage_broadcasts" => "Outage notices",
        "payment_succeeded" => "Payment confirmations",
    ],
];
