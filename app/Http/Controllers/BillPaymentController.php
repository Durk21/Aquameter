<?php

namespace App\Http\Controllers;

use App\Enums\PaymentTransactionStatus;
use App\Models\Bill;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Notifications\CashPaymentIntentLogged;
use App\Services\MpesaService;
use App\Services\PesapalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class BillPaymentController extends Controller
{
    public function show(Bill $bill): Response
    {
        Gate::authorize("pay", $bill);

        if ($bill->payment) {
            abort(422, "This bill has already been paid.");
        }

        $pending = $bill->pendingPaymentTransaction();

        return Inertia::render("Customer/Bills/Pay", [
            "bill" => [
                "id" => $bill->id,
                "amount" => $bill->amount,
                "due_date" => $bill->due_date->toDateString(),
                "status" => $bill->status->value,
            ],
            "pendingTransaction" => $pending ? $this->present($pending) : null,
        ]);
    }

    public function mpesa(Request $request, Bill $bill): JsonResponse
    {
        Gate::authorize("pay", $bill);

        if ($bill->payment) {
            return response()->json(["errors" => ["bill" => ["This bill has already been paid."]]], 422);
        }

        $validator = Validator::make($request->all(), [
            "phone" => ["required", "regex:/^(?:254|0)7[0-9]{8}$/"],
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $transaction = PaymentTransaction::create([
            "bill_id" => $bill->id,
            "account_id" => $bill->account_id,
            "initiated_by" => $request->user()->id,
            "method" => "mpesa",
            "status" => PaymentTransactionStatus::Pending,
            "amount" => $bill->amount,
            "phone" => $this->normalizePhone($validator->validated()["phone"]),
            "gateway" => "mpesa",
        ]);

        MpesaService::initiateStkPush($transaction);
        $transaction->refresh();

        return response()->json(["transaction" => $this->present($transaction)]);
    }

    public function bank(Request $request, Bill $bill): JsonResponse
    {
        Gate::authorize("pay", $bill);

        if ($bill->payment) {
            return response()->json(["errors" => ["bill" => ["This bill has already been paid."]]], 422);
        }

        $transaction = PaymentTransaction::create([
            "bill_id" => $bill->id,
            "account_id" => $bill->account_id,
            "initiated_by" => $request->user()->id,
            "method" => "bank_transfer",
            "status" => PaymentTransactionStatus::Pending,
            "amount" => $bill->amount,
            "gateway" => "pesapal",
        ]);

        $redirectUrl = PesapalService::submitOrder($transaction);
        $transaction->refresh();

        return response()->json([
            "transaction" => $this->present($transaction),
            "redirect_url" => $redirectUrl,
        ]);
    }

    public function cash(Request $request, Bill $bill): JsonResponse
    {
        Gate::authorize("pay", $bill);

        if ($bill->payment) {
            return response()->json(["errors" => ["bill" => ["This bill has already been paid."]]], 422);
        }

        $transaction = PaymentTransaction::create([
            "bill_id" => $bill->id,
            "account_id" => $bill->account_id,
            "initiated_by" => $request->user()->id,
            "method" => "cash",
            "status" => PaymentTransactionStatus::Pending,
            "amount" => $bill->amount,
        ]);

        Notification::send(User::role(config("roles.admin"))->get(), new CashPaymentIntentLogged($transaction));

        return response()->json(["transaction" => $this->present($transaction)]);
    }

    protected function normalizePhone(string $phone): string
    {
        return str_starts_with($phone, "0") ? "254".substr($phone, 1) : $phone;
    }

    protected function present(PaymentTransaction $transaction): array
    {
        return [
            "id" => $transaction->id,
            "method" => $transaction->method,
            "status" => $transaction->status->value,
            "gateway" => $transaction->gateway,
            "failure_reason" => $transaction->failure_reason,
        ];
    }
}
