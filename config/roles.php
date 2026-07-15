<?php

return [
    /*
    |--------------------------------------------------------------------
    | System Roles
    |--------------------------------------------------------------------
    | Single source of truth for role slugs. Never hardcode a role
    | string anywhere else — always reference config("roles.customer")
    | etc., or loop over config("roles.all") when seeding/iterating.
    */

    "customer"   => "customer",
    "technician" => "technician",
    "admin"      => "admin",
    "management" => "management",

    "all" => [
        "customer",
        "technician",
        "admin",
        "management",
    ],

    /*
    |--------------------------------------------------------------------
    | Staff Roles
    |--------------------------------------------------------------------
    | Roles an admin may assign when creating a staff account. Excludes
    | "customer" — customers only ever come from self-registration.
    */

    "staff" => [
        "admin",
        "technician",
        "management",
    ],
];
