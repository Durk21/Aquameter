<?php

use App\Enums\BillStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Meter;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

function createPayableBill(User $customer, User $technician): Bill
{
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

function mpesaConfig(): void
{
    config([
        "services.mpesa.consumer_key" => "test-key",
        "services.mpesa.consumer_secret" => "test-secret",
        "services.mpesa.shortcode" => "174379",
        "services.mpesa.passkey" => "test-passkey",
        "services.mpesa.callback_url" => "https://example.com/webhooks/mpesa/callback",
    ]);
}

function pesapalConfig(): void
{
    config([
        "services.pesapal.consumer_key" => "test-key",
        "services.pesapal.consumer_secret" => "test-secret",
        "services.pesapal.callback_url" => "https://example.com/webhooks/pesapal/ipn",
    ]);
}

it("shows the payment method selection page for the bill owner", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $bill = createPayableBill($customer, $technician);

    $response = $this->actingAs($customer)->get("/customer/bills/{$bill->id}/pay");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component("Customer/Bills/Pay"));
});

it("forbids a customer from paying another customer's bill", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $bill = createPayableBill($otherCustomer, $technician);

    $this->actingAs($customer)->get("/customer/bills/{$bill->id}/pay")->assertForbidden();
});

it("rejects an invalid phone number for mpesa", function () {
    mpesaConfig();
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($customer, $technician);

    $response = $this->actingAs($customer)->postJson("/customer/bills/{$bill->id}/pay/mpesa", [
        "phone" => "not-a-phone",
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseCount("payment_transactions", 0);
});

it("initiates an mpesa stk push and stores the checkout request id", function () {
    mpesaConfig();
    Http::fake([
        "sandbox.safaricom.co.ke/oauth/*" => Http::response(["access_token" => "token-123", "expires_in" => "3599"]),
        "sandbox.safaricom.co.ke/mpesa/stkpush/*" => Http::response([
            "MerchantRequestID" => "29115-1",
            "CheckoutRequestID" => "ws_CO_123",
            "ResponseCode" => "0",
            "ResponseDescription" => "Success. Request accepted for processing",
            "CustomerMessage" => "Success. Request accepted for processing",
        ]),
    ]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($customer, $technician);

    $response = $this->actingAs($customer)->postJson("/customer/bills/{$bill->id}/pay/mpesa", [
        "phone" => "0712345678",
    ]);

    $response->assertOk();
    expect($response->json("transaction.status"))->toBe("pending");

    $this->assertDatabaseHas("payment_transactions", [
        "bill_id" => $bill->id,
        "method" => "mpesa",
        "checkout_request_id" => "ws_CO_123",
        "phone" => "254712345678",
        "status" => "pending",
    ]);
});

it("marks the transaction failed when mpesa declines the stk push", function () {
    mpesaConfig();
    Http::fake([
        "sandbox.safaricom.co.ke/oauth/*" => Http::response(["access_token" => "token-123"]),
        "sandbox.safaricom.co.ke/mpesa/stkpush/*" => Http::response([
            "ResponseCode" => "1",
            "ResponseDescription" => "Invalid PhoneNumber",
        ]),
    ]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($customer, $technician);

    $response = $this->actingAs($customer)->postJson("/customer/bills/{$bill->id}/pay/mpesa", [
        "phone" => "0712345678",
    ]);

    $response->assertOk();
    expect($response->json("transaction.status"))->toBe("failed");
    $this->assertDatabaseHas("payment_transactions", ["bill_id" => $bill->id, "status" => "failed"]);
});

it("returns a friendly failure when mpesa isn't configured", function () {
    Http::fake();

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($customer, $technician);

    $response = $this->actingAs($customer)->postJson("/customer/bills/{$bill->id}/pay/mpesa", [
        "phone" => "0712345678",
    ]);

    $response->assertOk();
    expect($response->json("transaction.failure_reason"))->toContain("isn't configured yet");
    Http::assertNothingSent();
});

it("initiates a pesapal order and returns a redirect url", function () {
    pesapalConfig();
    Http::fake([
        "cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken" => Http::response(["token" => "token-123", "status" => "200"]),
        "cybqa.pesapal.com/pesapalv3/api/URLSetup/RegisterIPN" => Http::response(["ipn_id" => "ipn-123"]),
        "cybqa.pesapal.com/pesapalv3/api/Transactions/SubmitOrderRequest" => Http::response([
            "order_tracking_id" => "trk-123",
            "merchant_reference" => "aqm-ref",
            "redirect_url" => "https://cybqa.pesapal.com/pesapaliframe/checkout",
            "error" => null,
            "status" => "200",
        ]),
    ]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($customer, $technician);

    $response = $this->actingAs($customer)->postJson("/customer/bills/{$bill->id}/pay/bank");

    $response->assertOk();
    expect($response->json("redirect_url"))->toBe("https://cybqa.pesapal.com/pesapaliframe/checkout");

    $this->assertDatabaseHas("payment_transactions", [
        "bill_id" => $bill->id,
        "method" => "bank_transfer",
        "order_tracking_id" => "trk-123",
        "status" => "pending",
    ]);
});

it("marks the transaction failed when pesapal declines the order", function () {
    pesapalConfig();
    Http::fake([
        "cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken" => Http::response(["token" => "token-123", "status" => "200"]),
        "cybqa.pesapal.com/pesapalv3/api/URLSetup/RegisterIPN" => Http::response(["ipn_id" => "ipn-123"]),
        "cybqa.pesapal.com/pesapalv3/api/Transactions/SubmitOrderRequest" => Http::response([
            "error" => ["error_type" => "api_error", "code" => "invalid_request", "message" => "Invalid amount"],
            "status" => "500",
        ]),
    ]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($customer, $technician);

    $response = $this->actingAs($customer)->postJson("/customer/bills/{$bill->id}/pay/bank");

    $response->assertOk();
    expect($response->json("redirect_url"))->toBeNull();
    $this->assertDatabaseHas("payment_transactions", ["bill_id" => $bill->id, "status" => "failed"]);
});

it("logs a cash payment intent and notifies admins", function () {
    Notification::fake();

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($customer, $technician);

    $response = $this->actingAs($customer)->postJson("/customer/bills/{$bill->id}/pay/cash");

    $response->assertOk();
    $this->assertDatabaseHas("payment_transactions", [
        "bill_id" => $bill->id,
        "method" => "cash",
        "status" => "pending",
    ]);

    Notification::assertSentTo($admin, \App\Notifications\CashPaymentIntentLogged::class);
});

it("rejects initiating a payment on an already-paid bill", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($customer, $technician);

    $this->actingAs($admin)->post("/admin/bills/{$bill->id}/payments", [
        "amount" => $bill->amount,
        "method" => "cash",
        "paid_at" => now()->toDateString(),
    ]);

    $response = $this->actingAs($customer)->postJson("/customer/bills/{$bill->id}/pay/cash");

    $response->assertStatus(422);
});

it("polls mpesa status and finalizes the bill when completed", function () {
    mpesaConfig();
    Http::fake([
        "sandbox.safaricom.co.ke/oauth/*" => Http::response(["access_token" => "token-123"]),
        "sandbox.safaricom.co.ke/mpesa/stkpushquery/*" => Http::response([
            "ResponseCode" => "0",
            "ResultCode" => "0",
            "ResultDesc" => "The service request is processed successfully.",
        ]),
    ]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($customer, $technician);

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $customer->id,
        "method" => "mpesa",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
        "phone" => "254712345678",
        "gateway" => "mpesa",
        "checkout_request_id" => "ws_CO_123",
    ]);

    $response = $this->actingAs($customer)->getJson("/customer/payment-transactions/{$transaction->id}/status");

    $response->assertOk();
    expect($response->json("status"))->toBe("completed");
    expect($bill->fresh()->status)->toBe(BillStatus::Paid);
    $this->assertDatabaseHas("payments", ["bill_id" => $bill->id, "method" => "mpesa"]);
});

it("treats a still-processing mpesa query response as pending, not failed", function () {
    mpesaConfig();
    Http::fake([
        "sandbox.safaricom.co.ke/oauth/*" => Http::response(["access_token" => "token-123"]),
        "sandbox.safaricom.co.ke/mpesa/stkpushquery/*" => Http::response([
            "requestId" => "abc",
            "errorCode" => "500.001.1001",
            "errorMessage" => "The transaction is being processed",
        ], 500),
    ]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($customer, $technician);

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $customer->id,
        "method" => "mpesa",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
        "phone" => "254712345678",
        "gateway" => "mpesa",
        "checkout_request_id" => "ws_CO_123",
    ]);

    $response = $this->actingAs($customer)->getJson("/customer/payment-transactions/{$transaction->id}/status");

    $response->assertOk();
    expect($response->json("status"))->toBe("pending");
    expect($bill->fresh()->status)->toBe(BillStatus::Pending);
});

it("polls pesapal status and finalizes the bill when completed", function () {
    pesapalConfig();
    Http::fake([
        "cybqa.pesapal.com/pesapalv3/api/Auth/RequestToken" => Http::response(["token" => "token-123", "status" => "200"]),
        "cybqa.pesapal.com/pesapalv3/api/Transactions/GetTransactionStatus*" => Http::response([
            "payment_status_description" => "COMPLETED",
            "amount" => 1000,
        ]),
    ]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($customer, $technician);

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $customer->id,
        "method" => "bank_transfer",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
        "gateway" => "pesapal",
        "order_tracking_id" => "trk-123",
    ]);

    $response = $this->actingAs($customer)->getJson("/customer/payment-transactions/{$transaction->id}/status");

    $response->assertOk();
    expect($response->json("status"))->toBe("completed");
    expect($bill->fresh()->status)->toBe(BillStatus::Paid);
});

it("prevents polling another customer's transaction status", function () {
    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));
    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));
    $bill = createPayableBill($otherCustomer, $technician);

    $transaction = PaymentTransaction::create([
        "bill_id" => $bill->id,
        "account_id" => $bill->account_id,
        "initiated_by" => $otherCustomer->id,
        "method" => "cash",
        "status" => PaymentTransactionStatus::Pending,
        "amount" => $bill->amount,
    ]);

    $this->actingAs($customer)
        ->getJson("/customer/payment-transactions/{$transaction->id}/status")
        ->assertForbidden();
});
