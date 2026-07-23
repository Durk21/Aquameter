<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Payment;
use App\Services\PaymentReceiptPdfService;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        PaymentService::recordPayment($bill, $validated, $request->user());

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
}
