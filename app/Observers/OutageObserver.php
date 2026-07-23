<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Outage;

class OutageObserver
{
    public function updated(Outage $outage): void
    {
        if (! $outage->wasChanged("status")) {
            return;
        }

        ActivityLog::recordStatusChange($outage, $outage->getOriginal("status"), $outage->status);
    }
}
