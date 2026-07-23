<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Complaint;

class ComplaintObserver
{
    public function updated(Complaint $complaint): void
    {
        if (! $complaint->wasChanged("status")) {
            return;
        }

        ActivityLog::recordStatusChange($complaint, $complaint->getOriginal("status"), $complaint->status, $complaint->resolution_notes);
    }
}
