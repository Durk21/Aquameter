<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Bill;

class BillObserver
{
    public function updated(Bill $bill): void
    {
        if (! $bill->wasChanged("status")) {
            return;
        }

        ActivityLog::recordStatusChange($bill, $bill->getOriginal("status"), $bill->status);
    }
}
