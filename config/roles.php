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
];
