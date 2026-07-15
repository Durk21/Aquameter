<?php

namespace App\Services;

use App\Models\Account;

class AccountNumberGenerator
{
    public static function generate(): string
    {
        $prefix = config("utility.account_number_prefix");

        do {
            $candidate = $prefix."-".str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
        } while (Account::where("account_number", $candidate)->exists());

        return $candidate;
    }
}
