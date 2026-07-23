<?php

namespace App\Services;

use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PesapalService
{
    /**
     * Submits the order and stores the tracking identifiers. Returns
     * the hosted checkout redirect_url on success, null on failure —
     * never throws, matching AiChatService's defensive shape.
     */
    public static function submitOrder(PaymentTransaction $transaction): ?string
    {
        if (blank(config("services.pesapal.consumer_key"))) {
            $transaction->update([
                "status" => PaymentTransactionStatus::Failed,
                "failure_reason" => "Bank/card payments aren't configured yet — an admin needs to add Pesapal credentials.",
            ]);

            return null;
        }

        $token = self::getAccessToken();
        $ipnId = $token ? self::registerIpnOnce($token) : null;

        if (! $token || ! $ipnId) {
            $transaction->update([
                "status" => PaymentTransactionStatus::Failed,
                "failure_reason" => "Could not authenticate with Pesapal.",
            ]);

            return null;
        }

        $merchantReference = "aqm-{$transaction->id}-".now()->timestamp;
        $user = $transaction->initiatedBy;

        try {
            $response = Http::withToken($token)->post(self::baseUrl()."/api/Transactions/SubmitOrderRequest", [
                "id" => $merchantReference,
                "currency" => "KES",
                "amount" => (float) $transaction->amount,
                "description" => "Aquameter bill #{$transaction->bill_id}",
                "callback_url" => config("services.pesapal.callback_url"),
                "notification_id" => $ipnId,
                "billing_address" => [
                    "email_address" => $user->email,
                    "phone_number" => $transaction->phone,
                    "first_name" => $user->name,
                ],
            ]);

            $data = $response->json() ?? [];

            if ($response->successful() && ! empty($data["redirect_url"]) && empty($data["error"])) {
                $transaction->update([
                    "merchant_reference" => $merchantReference,
                    "order_tracking_id" => $data["order_tracking_id"] ?? null,
                ]);

                return $data["redirect_url"];
            }

            $transaction->update([
                "status" => PaymentTransactionStatus::Failed,
                "failure_reason" => $data["error"]["message"] ?? "Pesapal declined the request.",
            ]);

            return null;
        } catch (Throwable $e) {
            Log::error("Pesapal order submission failed", ["transaction_id" => $transaction->id, "exception" => $e->getMessage()]);

            $transaction->update([
                "status" => PaymentTransactionStatus::Failed,
                "failure_reason" => "Could not reach Pesapal. Please try again.",
            ]);

            return null;
        }
    }

    /**
     * The IPN payload's own status must never be trusted directly —
     * Pesapal's own docs require re-verifying via this endpoint before
     * finalizing anything. Returns "completed", "failed", or "pending"
     * — never throws.
     */
    public static function getTransactionStatus(string $orderTrackingId): string
    {
        $token = self::getAccessToken();

        if (! $token) {
            return "pending";
        }

        try {
            $response = Http::withToken($token)
                ->get(self::baseUrl()."/api/Transactions/GetTransactionStatus", [
                    "orderTrackingId" => $orderTrackingId,
                ]);

            $data = $response->json() ?? [];

            if (! $response->successful()) {
                return "pending";
            }

            return match ($data["payment_status_description"] ?? null) {
                "COMPLETED" => "completed",
                "FAILED", "INVALID", "REVERSED" => "failed",
                default => "pending",
            };
        } catch (Throwable $e) {
            Log::error("Pesapal transaction status check failed", ["order_tracking_id" => $orderTrackingId, "exception" => $e->getMessage()]);

            return "pending";
        }
    }

    protected static function getAccessToken(): ?string
    {
        $cached = Cache::get("pesapal_access_token");

        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::post(self::baseUrl()."/api/Auth/RequestToken", [
                "consumer_key" => config("services.pesapal.consumer_key"),
                "consumer_secret" => config("services.pesapal.consumer_secret"),
            ]);

            $token = $response->successful() ? $response->json("token") : null;

            if ($token) {
                // Pesapal tokens are short-lived (~5 min); cache briefly
                // rather than re-authenticating on every call within a
                // single request lifecycle.
                Cache::put("pesapal_access_token", $token, now()->addMinutes(4));
            }

            return $token;
        } catch (Throwable $e) {
            Log::error("Pesapal auth request failed", ["exception" => $e->getMessage()]);

            return null;
        }
    }

    /**
     * IPN registration is meant to be one-time per environment —
     * cached indefinitely once obtained rather than re-registered on
     * every order (harmless if repeated, but wasted work).
     */
    protected static function registerIpnOnce(string $token): ?string
    {
        $cached = Cache::get("pesapal_ipn_id");

        if ($cached) {
            return $cached;
        }

        try {
            $response = Http::withToken($token)->post(self::baseUrl()."/api/URLSetup/RegisterIPN", [
                "url" => config("services.pesapal.callback_url"),
                "ivalue" => "GET",
            ]);

            $data = $response->json() ?? [];
            $ipnId = $response->successful() ? ($data["ipn_id"] ?? null) : null;

            if ($ipnId) {
                Cache::forever("pesapal_ipn_id", $ipnId);
            }

            return $ipnId;
        } catch (Throwable $e) {
            Log::error("Pesapal IPN registration failed", ["exception" => $e->getMessage()]);

            return null;
        }
    }

    protected static function baseUrl(): string
    {
        return config("services.pesapal.env") === "production"
            ? "https://pay.pesapal.com/v3"
            : "https://cybqa.pesapal.com/pesapalv3";
    }
}
