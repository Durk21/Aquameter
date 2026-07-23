<?php

namespace App\Notifications;

use App\Models\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CashPaymentIntentLogged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected PaymentTransaction $transaction)
    {
    }

    /**
     * Always mail + database — this goes to admins for operational
     * awareness, not the customer-facing notification_preferences toggle.
     */
    public function via(object $notifiable): array
    {
        return ["mail", "database"];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Cash payment expected — KES {$this->transaction->amount}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->summary())
            ->action("Review Pending Cash Payments", route("admin.payment-transactions.index"))
            ->line("Confirm it once the customer pays in person.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            "type" => "cash_payment_intent_logged",
            "title" => "Cash payment expected",
            "message" => $this->summary(),
            "payment_transaction_id" => $this->transaction->id,
            "action_route" => "admin.payment-transactions.index",
        ];
    }

    protected function summary(): string
    {
        return "A customer intends to pay KES {$this->transaction->amount} in cash for bill #{$this->transaction->bill_id}.";
    }
}
