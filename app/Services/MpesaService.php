<?php

namespace App\Services;

use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MpesaService
{
    /**
     * Sends the STK push and stores the resulting checkout_request_id.
     * Never throws — a failure marks the transaction Failed with a
     * reason instead, matching AiChatService's defensive shape.
     */
    public static function initiateStkPush(PaymentTransaction $transaction): bool
    {
        if (blank(config("services.mpesa.consumer_key"))) {
            $transaction->update([
                "status" => PaymentTransactionStatus::Failed,
                "failure_reason" => "M-Pesa isn't configured yet — an admin needs to add Safaricom credentials.",
            ]);

            return false;
        }

        $token = self::getAccessToken();

        if (! $token) {
            $transaction->update([
                "status" => PaymentTransactionStatus::Failed,
                "failure_reason" => "Could not authenticate with M-Pesa.",
            ]);

            return false;
        }

        [$timestamp, $password] = self::timestampAndPassword();

        try {
            $response = Http::withToken($token)->post(self::baseUrl()."/mpesa/stkpush/v1/processrequest", [
                "BusinessShortCode" => config("services.mpesa.shortcode"),
                "Password" => $password,
                "Timestamp" => $timestamp,
                "TransactionType" => "CustomerPayBillOnline",
                "Amount" => (int) round((float) $transaction->amount),
                "PartyA" => $transaction->phone,
                "PartyB" => config("services.mpesa.shortcode"),
                "PhoneNumber" => $transaction->phone,
                "CallBackURL" => config("services.mpesa.callback_url"),
                "AccountReference" => $transaction->bill->account->account_number,
                "TransactionDesc" => "Aquameter bill #{$transaction->bill_id}",
            ]);

            $data = $response->json() ?? [];

            if ($response->successful() && ($data["ResponseCode"] ?? null) === "0") {
                $transaction->update(["checkout_request_id" => $data["CheckoutRequestID"] ?? null]);

                return true;
            }

            $transaction->update([
                "status" => PaymentTransactionStatus::Failed,
                "failure_reason" => $data["errorMessage"] ?? $data["ResponseDescription"] ?? "M-Pesa declined the request.",
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error("M-Pesa STK push failed", ["transaction_id" => $transaction->id, "exception" => $e->getMessage()]);

            $transaction->update([
                "status" => PaymentTransactionStatus::Failed,
                "failure_reason" => "Could not reach M-Pesa. Please try again.",
            ]);

            return false;
        }
    }

    /**
     * Actively re-checks a pending STK push rather than only waiting on
     * the passive callback — this sandbox has no public URL to receive
     * one, and Safaricom itself recommends the dual approach. Returns
     * "completed", "failed", or "pending" — never throws, and critically
     * treats Daraja's "still awaiting PIN entry" error shape as pending,
     * not failed.
     */
    public static function queryStkPushStatus(PaymentTransaction $transaction): string
    {
        if (! $transaction->checkout_request_id) {
            return "pending";
        }

        $token = self::getAccessToken();

        if (! $token) {
            return "pending";
        }

        [$timestamp, $password] = self::timestampAndPassword();

        try {
            $response = Http::withToken($token)->post(self::baseUrl()."/mpesa/stkpushquery/v1/query", [
                "BusinessShortCode" => config("services.mpesa.shortcode"),
                "Password" => $password,
                "Timestamp" => $timestamp,
                "CheckoutRequestID" => $transaction->checkout_request_id,
            ]);

            $data = $response->json() ?? [];

            // Still awaiting PIN entry — Daraja returns an error shape
            // (e.g. errorCode "500.001.1001"), not a normal ResultCode.
            if (! $response->successful() || isset($data["errorCode"])) {
                return "pending";
            }

            $resultCode = $data["ResultCode"] ?? null;

            return ((string) $resultCode === "0") ? "completed" : "failed";
        } catch (Throwable $e) {
            Log::error("M-Pesa STK push status query failed", ["transaction_id" => $transaction->id, "exception" => $e->getMessage()]);

            return "pending";
        }
    }

    protected static function getAccessToken(): ?string
    {
        $cached = Cache::get("mpesa_access_token");

        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::withBasicAuth(config("services.mpesa.consumer_key"), config("services.mpesa.consumer_secret"))
                ->get(self::baseUrl()."/oauth/v1/generate?grant_type=client_credentials");

            $token = $response->successful() ? $response->json("access_token") : null;

            if ($token) {
                Cache::put("mpesa_access_token", $token, now()->addMinutes(55));
            }

            return $token;
        } catch (Throwable $e) {
            Log::error("M-Pesa OAuth token request failed", ["exception" => $e->getMessage()]);

            return null;
        }
    }

    protected static function timestampAndPassword(): array
    {
        $timestamp = now()->format("YmdHis");
        $password = base64_encode(config("services.mpesa.shortcode").config("services.mpesa.passkey").$timestamp);

        return [$timestamp, $password];
    }

    protected static function baseUrl(): string
    {
        return config("services.mpesa.env") === "production"
            ? "https://api.safaricom.co.ke"
            : "https://sandbox.safaricom.co.ke";
    }
}
