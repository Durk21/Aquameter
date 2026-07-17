<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\BillStatus;
use App\Enums\WorkOrderType;
use App\Models\Bill;
use App\Models\Payment;
use App\Services\PaymentReceiptPdfService;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentController extends Controller
{
    public function create(Request $request, Bill $bill): Response
    {
        Gate::authorize("create", Payment::class);

        if ($bill->payment) {
            abort(422, "This bill has already been paid.");
        }

        $bill->load("account.user");

        return Inertia::render("Admin/Payments/Create", [
            "bill" => [
                "id" => $bill->id,
                "amount" => $bill->amount,
                "due_date" => $bill->due_date->toDateString(),
                "status" => $bill->status->value,
                "customer_name" => $bill->account->user->name,
                "account_number" => $bill->account->account_number,
                "phone" => $bill->account->phone,
            ],
            "paymentMethods" => config("utility.payment_methods"),
        ]);
    }

    public function store(Request $request, Bill $bill): RedirectResponse
    {
        Gate::authorize("create", Payment::class);

        if ($bill->payment) {
            abort(422, "This bill has already been paid.");
        }

        $validated = $request->validate([
            "amount" => "required|numeric",
            "method" => ["required", Rule::in(config("utility.payment_methods"))],
            "reference" => "nullable|string|max:255",
            "paid_at" => "required|date|before_or_equal:today",
        ]);

        if (round((float) $validated["amount"], 2) !== round((float) $bill->amount, 2)) {
            return back()->withErrors([
                "amount" => "Payment must match the full bill amount of ".$bill->amount.".",
            ])->withInput();
        }

        DB::transaction(function () use ($bill, $validated, $request) {
            Payment::create([
                "bill_id" => $bill->id,
                "account_id" => $bill->account_id,
                "recorded_by" => $request->user()->id,
                "amount" => $validated["amount"],
                "method" => $validated["method"],
                "reference" => $validated["reference"] ?? null,
                "paid_at" => $validated["paid_at"],
            ]);

            $bill->status = BillStatus::Paid;
            $bill->paid_at = $validated["paid_at"];
            $bill->save();

            $this->settleAccountStatus($bill, $request);
        });

        return redirect()
            ->route("admin.bills.index")
            ->with("status", "Payment recorded. Bill marked as paid.");
    }

    public function downloadReceipt(Payment $payment): BinaryFileResponse
    {
        Gate::authorize("view", $payment);

        $path = PaymentReceiptPdfService::generate($payment);

        return response()->download($path, "aquameter-receipt-{$payment->id}.pdf")->deleteFileAfterSend();
    }

    /**
     * A fully paid bill clears an Overdue/Defaulted account back to
     * Active once nothing else is outstanding. A Disconnected account
     * is never flipped directly — it gets its own reconnection work
     * order, dispatched to a technician like any other job.
     */
    protected function settleAccountStatus(Bill $bill, Request $request): void
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
                WorkOrderService::initiateReconnection($account, $request->user(), $bill);
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
