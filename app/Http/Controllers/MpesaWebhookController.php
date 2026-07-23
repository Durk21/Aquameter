<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MpesaWebhookController extends Controller
{
    /**
     * Public, unauthenticated — Safaricom calls this server-to-server.
     * Regardless of what happens internally (unknown transaction,
     * already finalized, a DB error), Safaricom expects a 200 with
     * this ack shape or it retries the callback indefinitely.
     */
    public function callback(Request $request): JsonResponse
    {
        try {
            $callback = $request->input("Body.stkCallback", []);
            $checkoutRequestId = $callback["CheckoutRequestID"] ?? null;

            $transaction = $checkoutRequestId
                ? PaymentTransaction::where("checkout_request_id", $checkoutRequestId)->first()
                : null;

            if (! $transaction) {
                Log::error("M-Pesa callback for unknown transaction", ["checkout_request_id" => $checkoutRequestId]);

                return $this->ack();
            }

            if ((int) ($callback["ResultCode"] ?? -1) === 0) {
                $receipt = collect($callback["CallbackMetadata"]["Item"] ?? [])
                    ->firstWhere("Name", "MpesaReceiptNumber")["Value"] ?? null;

                PaymentService::finalizeTransaction($transaction, $receipt ? (string) $receipt : null);
            } else {
                PaymentService::failTransaction($transaction, $callback["ResultDesc"] ?? "Payment was not completed.");
            }
        } catch (Throwable $e) {
            Log::error("M-Pesa callback handling failed", ["exception" => $e->getMessage()]);
        }

        return $this->ack();
    }

    protected function ack(): JsonResponse
    {
        return response()->json(["ResultCode" => 0, "ResultDesc" => "Accepted"]);
    }
}
