<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\BillStatus;
use App\Enums\PaymentTransactionStatus;
use App\Enums\WorkOrderType;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Notifications\PaymentSucceeded;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    /**
     * Single source of truth for "a bill just got paid" — used by the
     * admin manual-entry flow, gateway webhook/poll finalization, and
     * cash-intent confirmation alike. Idempotent: a bill that already
     * has a Payment just returns it, so a retried webhook delivery or
     * a double-submit never creates a duplicate.
     */
    public static function recordPayment(Bill $bill, array $data, User $recordedBy): Payment
    {
        if ($bill->payment) {
            return $bill->payment;
        }

        return DB::transaction(function () use ($bill, $data, $recordedBy) {
            $payment = Payment::create([
                "bill_id" => $bill->id,
                "account_id" => $bill->account_id,
                "recorded_by" => $recordedBy->id,
                "amount" => $data["amount"],
                "method" => $data["method"],
                "reference" => $data["reference"] ?? null,
                "paid_at" => $data["paid_at"],
            ]);

            $bill->status = BillStatus::Paid;
            $bill->paid_at = $data["paid_at"];
            $bill->save();

            self::settleAccountStatus($bill, $recordedBy);

            $bill->account->user->notify(new PaymentSucceeded($payment));

            return $payment;
        });
    }

    /**
     * Shared finalization for a gateway-confirmed PaymentTransaction —
     * used by both the active status-poll endpoint and the passive
     * webhook handlers, which can race each other since this sandbox
     * has no public URL to reliably receive the passive callback.
     * Locks the transaction row so only one caller ever finalizes it;
     * a transaction that's already Completed/Failed is a no-op.
     *
     * $recordedBy defaults to whoever initiated the transaction (the
     * customer — correct for an automatic gateway confirmation, since
     * no staff were involved). Cash confirmation is the one path that
     * overrides this with the confirming admin instead.
     */
    public static function finalizeTransaction(PaymentTransaction $transaction, ?string $reference = null, ?User $recordedBy = null): ?Payment
    {
        return DB::transaction(function () use ($transaction, $reference, $recordedBy) {
            $locked = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PaymentTransactionStatus::Pending) {
                return $locked->payment;
            }

            $payment = self::recordPayment($locked->bill, [
                "amount" => $locked->amount,
                "method" => $locked->method,
                "reference" => $reference ?? $locked->checkout_request_id ?? $locked->order_tracking_id,
                "paid_at" => now(),
            ], $recordedBy ?? $locked->initiatedBy);

            $locked->update([
                "status" => PaymentTransactionStatus::Completed,
                "payment_id" => $payment->id,
                "confirmed_at" => now(),
            ]);

            return $payment;
        });
    }

    /**
     * Same locking/idempotency reasoning as finalizeTransaction() — a
     * transaction no longer Pending is left untouched.
     */
    public static function failTransaction(PaymentTransaction $transaction, string $reason): void
    {
        DB::transaction(function () use ($transaction, $reason) {
            $locked = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PaymentTransactionStatus::Pending) {
                return;
            }

            $locked->update([
                "status" => PaymentTransactionStatus::Failed,
                "failure_reason" => $reason,
            ]);
        });
    }

    /**
     * A fully paid bill clears an Overdue/Defaulted account back to
     * Active once nothing else is outstanding. A Disconnected account
     * is never flipped directly — it gets its own reconnection work
     * order, dispatched to a technician like any other job.
     *
     * $actor may be null when a gateway confirms a payment with no
     * staff involved (webhook/poll) — WorkOrderService::initiateReconnection
     * only uses it to record who created the work order, and
     * ActivityLog::recordStatusChange() already tolerates a null actor.
     */
    public static function settleAccountStatus(Bill $bill, ?User $actor): void
    {
        $account = $bill->account;

        $hasOutstanding = Bill::where("account_id", $account->id)
            ->whereIn("status", [BillStatus::Overdue, BillStatus::Defaulted])
            ->where("id", "!=", $bill->id)
            ->exists();

        if ($hasOutstanding) {
            return;
        }

        if ($account->status === AccountStatus::Disconnected) {
            if (! WorkOrderService::hasOpenWorkOrder($account, WorkOrderType::Reconnection)) {
                WorkOrderService::initiateReconnection($account, $actor, $bill);
            }

            return;
        }

        if (in_array($account->status, [AccountStatus::Overdue, AccountStatus::Defaulted], true)) {
            $account->status = AccountStatus::Active;
            $account->defaulted_at = null;
            $account->save();
        }
    }
}
