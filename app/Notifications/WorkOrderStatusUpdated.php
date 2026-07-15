<?php

namespace App\Notifications;

use App\Enums\WorkOrderStatus;
use App\Enums\WorkOrderType;
use App\Models\WorkOrder;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WorkOrderStatusUpdated extends Notification
{
    public function __construct(protected WorkOrder $workOrder)
    {
    }

    public function via(object $notifiable): array
    {
        return ["mail", "database"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Update on your {$this->workOrder->type->label()} work order")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->summary())
            ->action("View Account", route("customer.bills.index"))
            ->line("Thank you for using Aquameter.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            "type" => "work_order_status_updated",
            "title" => "Update on your {$this->workOrder->type->label()} work order",
            "message" => $this->summary(),
            "work_order_id" => $this->workOrder->id,
            "work_order_type" => $this->workOrder->type->value,
            "status" => $this->workOrder->status->value,
            "status_label" => $this->workOrder->status->label(),
            "action_route" => "customer.bills.index",
        ];
    }

    protected function summary(): string
    {
        return match (true) {
            $this->workOrder->type === WorkOrderType::Reconnection
                && $this->workOrder->status === WorkOrderStatus::Approved
                => "Your outstanding balance is settled. A technician has been dispatched to reconnect your service.",

            $this->workOrder->type === WorkOrderType::Reconnection
                && $this->workOrder->status === WorkOrderStatus::Completed
                => "Your water service has been reconnected.",

            $this->workOrder->type === WorkOrderType::Disconnection
                && $this->workOrder->status === WorkOrderStatus::Approved
                => "Your disconnection notice has been reviewed and approved. A technician will be dispatched shortly unless the balance is settled.",

            $this->workOrder->type === WorkOrderType::Disconnection
                && $this->workOrder->status === WorkOrderStatus::Completed
                => "Your water service has been disconnected due to an unpaid balance. Settle your balance to begin reconnection.",

            in_array($this->workOrder->type, [WorkOrderType::LeakRepair, WorkOrderType::ServiceRequest], true)
                && $this->workOrder->status === WorkOrderStatus::Approved
                => "Your {$this->workOrder->type->label()} has been logged and is in the technician dispatch queue.",

            in_array($this->workOrder->type, [WorkOrderType::LeakRepair, WorkOrderType::ServiceRequest], true)
                && $this->workOrder->status === WorkOrderStatus::Completed
                => "Your {$this->workOrder->type->label()} has been resolved.",

            $this->workOrder->status === WorkOrderStatus::Cancelled
                => "Your {$this->workOrder->type->label()} work order has been cancelled.",

            default => "Your {$this->workOrder->type->label()} work order status is now: {$this->workOrder->status->label()}.",
        };
    }
}
