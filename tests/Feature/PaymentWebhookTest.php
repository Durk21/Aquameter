<?php

use App\Enums\BillStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function createWebhookTestBill(): Bill
{
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["user_id" => $customer->id]);
    $meter = Meter::factory()->create(["account_id" => $account->id]);
    $reading = $meter->readings()->create([
        "recorded_by" => $technician->id,
        "reading_value" => 100,
        "reading_date" => now(),
    ]);

    return Bill::create([
        "account_id" => $account->id,
        "meter_reading_id" => $reading->id,
        "previous_reading_value" => 50,
        "current_reading_value" => 100,
        "units_consumed" => 50,
        "rate_applied" => config("utility.rate_per_unit"),
        "amount" => 50 * config("utility.rate_per_unit"),
        "status" => "pending",
        "due_date" => now()->addDays(7),
    ]);
}

it("finalizes a bill on a successful mpesa callback", function () {
    $bill = createWebhookTestBill();

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $bill->account->user_id,
        "method" => "mpesa",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
        "phone" => "254712345678",
        "gateway" => "mpesa",
        "checkout_request_id" => "ws_CO_123",
    ]);

    $response = $this->postJson("/webhooks/mpesa/callback", [
        "Body" => [
            "stkCallback" => [
                "MerchantRequestID" => "29115-1",
                "CheckoutRequestID" => "ws_CO_123",
                "ResultCode" => 0,
                "ResultDesc" => "The service request is processed successfully.",
                "CallbackMetadata" => [
                    "Item" => [
                        ["Name" => "Amount", "Value" => (float) $bill->amount],
                        ["Name" => "MpesaReceiptNumber", "Value" => "NLJ7RT61SV"],
                        ["Name" => "TransactionDate", "Value" => 20260723102115],
                        ["Name" => "PhoneNumber", "Value" => 254712345678],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk();
    $response->assertJson(["ResultCode" => 0]);

    expect($bill->fresh()->status)->toBe(BillStatus::Paid);
    expect($transaction->fresh()->status)->toBe(PaymentTransactionStatus::Completed);
    $this->assertDatabaseHas("payments", ["bill_id" => $bill->id, "reference" => "NLJ7RT61SV"]);
});

it("marks a transaction failed on a failed mpesa callback without touching the bill", function () {
    $bill = createWebhookTestBill();

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $bill->account->user_id,
        "method" => "mpesa",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
        "phone" => "254712345678",
        "gateway" => "mpesa",
        "checkout_request_id" => "ws_CO_456",
    ]);

    $response = $this->postJson("/webhooks/mpesa/callback", [
        "Body" => [
            "stkCallback" => [
                "MerchantRequestID" => "29115-2",
                "CheckoutRequestID" => "ws_CO_456",
                "ResultCode" => 1032,
                "ResultDesc" => "Request cancelled by user",
            ],
        ],
    ]);

    $response->assertOk();
    expect($transaction->fresh()->status)->toBe(PaymentTransactionStatus::Failed);
    expect($bill->fresh()->status)->toBe(BillStatus::Pending);
});

it("acks an mpesa callback for an unknown checkout request id without erroring", function () {
    $response = $this->postJson("/webhooks/mpesa/callback", [
        "Body" => [
            "stkCallback" => [
                "CheckoutRequestID" => "ws_CO_does_not_exist",
                "ResultCode" => 0,
            ],
        ],
    ]);

    $response->assertOk();
    $response->assertJson(["ResultCode" => 0]);
});

it("is idempotent against a duplicate mpesa callback delivery", function () {
    $bill = createWebhookTestBill();

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $bill->account->user_id,
        "method" => "mpesa",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
        "phone" => "254712345678",
        "gateway" => "mpesa",
        "checkout_request_id" => "ws_CO_789",
    ]);

    $payload = [
        "Body" => [
            "stkCallback" => [
                "CheckoutRequestID" => "ws_CO_789",
                "ResultCode" => 0,
                "CallbackMetadata" => [
                    "Item" => [
                        ["Name" => "MpesaReceiptNumber", "Value" => "ABC123"],
                    ],
                ],
            ],
        ],
    ];

    $this->postJson("/webhooks/mpesa/callback", $payload)->assertOk();
    $this->postJson("/webhooks/mpesa/callback", $payload)->assertOk();

    $this->assertDatabaseCount("payments", 1);
});

it("verifies via GetTransactionStatus before finalizing on a pesapal ipn, ignoring the ipn body's own status", function () {
    config([
        "services.pesapal.consumer_key" => "test-key",
        "services.pesapal.consumer_secret" => "test-secret",
    ]);

    Http::fake([
        "cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken" => Http::response(["token" => "token-123", "status" => "200"]),
        "cybqa.pesapal.com/pesapalv3/api/Transactions/GetTransactionStatus*" => Http::response([
            "payment_status_description" => "COMPLETED",
        ]),
    ]);

    $bill = createWebhookTestBill();

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $bill->account->user_id,
        "method" => "bank_transfer",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
        "gateway" => "pesapal",
        "order_tracking_id" => "trk-123",
        "merchant_reference" => "aqm-ref",
    ]);

    // The IPN payload itself carries no status — the handler must call
    // GetTransactionStatus rather than trusting anything in this body.
    $response = $this->postJson("/webhooks/pesapal/ipn", [
        "OrderTrackingId" => "trk-123",
        "OrderMerchantReference" => "aqm-ref",
        "OrderNotificationType" => "IPNCHANGE",
    ]);

    $response->assertOk();
    $response->assertJson(["orderTrackingId" => "trk-123"]);

    expect($bill->fresh()->status)->toBe(BillStatus::Paid);
    expect($transaction->fresh()->status)->toBe(PaymentTransactionStatus::Completed);

    Http::assertSent(fn ($request) => str_contains((string) $request->url(), "GetTransactionStatus"));
});

it("does not finalize a pesapal ipn when GetTransactionStatus reports pending", function () {
    config([
        "services.pesapal.consumer_key" => "test-key",
        "services.pesapal.consumer_secret" => "test-secret",
    ]);

    Http::fake([
        "cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken" => Http::response(["token" => "token-123", "status" => "200"]),
        "cybqa.pesapal.com/pesapalv3/api/Transactions/GetTransactionStatus*" => Http::response([
            "payment_status_description" => "PENDING",
        ]),
    ]);

    $bill = createWebhookTestBill();

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $bill->account->user_id,
        "method" => "bank_transfer",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
        "gateway" => "pesapal",
        "order_tracking_id" => "trk-456",
    ]);

    $this->postJson("/webhooks/pesapal/ipn", ["OrderTrackingId" => "trk-456"])->assertOk();

    expect($bill->fresh()->status)->toBe(BillStatus::Pending);
    expect($transaction->fresh()->status)->toBe(PaymentTransactionStatus::Pending);
});
