<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

class PaymentReceiptPdfService
{
    public static function generate(Payment $payment): string
    {
        $payment->loadMissing(["account.user", "bill", "recordedBy"]);

        $html = View::make("pdf.receipt", ["payment" => $payment])->render();

        $outputPath = storage_path("app/temp-receipt-".$payment->id."-".uniqid().".pdf");

        $browsershot = Browsershot::html($html)->format("A4")->margins(0, 0, 0, 0);

        $nodeBinary = config("services.browsershot.node_binary");
        if ($nodeBinary) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        if (config("services.browsershot.no_sandbox")) {
            $browsershot->noSandbox();
        }

        $browsershot->save($outputPath);

        return $outputPath;
    }
}
