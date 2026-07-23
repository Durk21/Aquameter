<?php

namespace App\Http\Controllers;

use App\Enums\PaymentTransactionStatus;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\MpesaService;
use App\Services\PaymentService;
use App\Services\PesapalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PaymentTransactionController extends Controller
{
    /**
     * Actively re-checks the gateway if still pending, rather than
     * only ever waiting on the passive webhook — this sandbox has no
     * public URL for Safaricom/Pesapal to reach, so this is the path
     * that actually finalizes a payment during local verification.
     */
    public function status(PaymentTransaction $paymentTransaction): JsonResponse
    {
        Gate::authorize("view", $paymentTransaction);

        if ($paymentTransaction->status === PaymentTransactionStatus::Pending) {
            $result = match ($paymentTransaction->gateway) {
                "mpesa" => MpesaService::queryStkPushStatus($paymentTransaction),
                "pesapal" => $paymentTransaction->order_tracking_id
                    ? PesapalService::getTransactionStatus($paymentTransaction->order_tracking_id)
                    : "pending",
                default => "pending",
            };

            if ($result === "completed") {
                PaymentService::finalizeTransaction($paymentTransaction);
            } elseif ($result === "failed") {
                PaymentService::failTransaction($paymentTransaction, "The payment was not completed.");
            }

            $paymentTransaction->refresh();
        }

        return response()->json([
            "id" => $paymentTransaction->id,
            "status" => $paymentTransaction->status->value,
            "failure_reason" => $paymentTransaction->failure_reason,
            "bill_id" => $paymentTransaction->bill_id,
        ]);
    }

    public function pesapalReturn(PaymentTransaction $paymentTransaction): Response
    {
        Gate::authorize("view", $paymentTransaction);

        return Inertia::render("Customer/Bills/PesapalReturn", [
            "transaction" => [
                "id" => $paymentTransaction->id,
                "status" => $paymentTransaction->status->value,
                "bill_id" => $paymentTransaction->bill_id,
            ],
        ]);
    }

    public function adminIndex(): Response
    {
        Gate::authorize("create", Payment::class);

        $transactions = PaymentTransaction::where("method", "cash")
            ->where("status", PaymentTransactionStatus::Pending)
            ->with(["initiatedBy", "account"])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PaymentTransaction $t) => [
                "id" => $t->id,
                "amount" => $t->amount,
                "customer_name" => $t->initiatedBy->name,
                "account_number" => $t->account->account_number,
                "bill_id" => $t->bill_id,
                "created_at" => $t->created_at->toDateTimeString(),
            ]);

        return Inertia::render("Admin/Payments/PendingCash", [
            "transactions" => $transactions,
        ]);
    }

    public function confirmCash(Request $request, PaymentTransaction $paymentTransaction): RedirectResponse
    {
        Gate::authorize("create", Payment::class);

        if ($paymentTransaction->method !== "cash" || $paymentTransaction->status !== PaymentTransactionStatus::Pending) {
            abort(422, "This payment intent can no longer be confirmed.");
        }

        PaymentService::finalizeTransaction(
            $paymentTransaction,
            "Cash — confirmed by {$request->user()->name}",
            $request->user(),
        );

        return redirect()
            ->route("admin.payment-transactions.index")
            ->with("status", "Cash payment confirmed. Bill marked as paid.");
    }
}
