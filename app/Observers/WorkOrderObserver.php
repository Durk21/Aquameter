<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\WorkOrder;

class WorkOrderObserver
{
    public function updated(WorkOrder $workOrder): void
    {
        if (! $workOrder->wasChanged("status")) {
            return;
        }

        ActivityLog::recordStatusChange($workOrder, $workOrder->getOriginal("status"), $workOrder->status);
    }
}
