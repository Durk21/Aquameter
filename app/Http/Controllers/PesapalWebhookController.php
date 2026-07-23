<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Services\PaymentService;
use App\Services\PesapalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PesapalWebhookController extends Controller
{
    /**
     * Public, unauthenticated — Pesapal calls this server-to-server.
     * Never trusts the IPN body's own status fields; always re-verifies
     * via PesapalService::getTransactionStatus() first, per Pesapal's
     * own integration guidance. Acks with the identifiers Pesapal
     * expects echoed back, regardless of internal outcome.
     */
    public function ipn(Request $request): JsonResponse
    {
        $orderTrackingId = $request->input("OrderTrackingId");
        $merchantReference = $request->input("OrderMerchantReference");
        $notificationType = $request->input("OrderNotificationType", "IPNCHANGE");

        try {
            $transaction = $orderTrackingId
                ? PaymentTransaction::where("order_tracking_id", $orderTrackingId)->first()
                : null;

            if (! $transaction) {
                Log::error("Pesapal IPN for unknown transaction", ["order_tracking_id" => $orderTrackingId]);

                return $this->ack($orderTrackingId, $merchantReference, $notificationType);
            }

            $result = PesapalService::getTransactionStatus($orderTrackingId);

            if ($result === "completed") {
                PaymentService::finalizeTransaction($transaction);
            } elseif ($result === "failed") {
                PaymentService::failTransaction($transaction, "The payment was not completed.");
            }
        } catch (Throwable $e) {
            Log::error("Pesapal IPN handling failed", ["exception" => $e->getMessage()]);
        }

        return $this->ack($orderTrackingId, $merchantReference, $notificationType);
    }

    protected function ack(?string $orderTrackingId, ?string $merchantReference, string $notificationType): JsonResponse
    {
        return response()->json([
            "orderNotificationType" => $notificationType,
            "orderTrackingId" => $orderTrackingId,
            "orderMerchantReference" => $merchantReference,
            "status" => 200,
        ]);
    }
}
