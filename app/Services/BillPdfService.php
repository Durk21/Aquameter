<?php

namespace App\Services;

use App\Models\Bill;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

class BillPdfService
{
    public static function generate(Bill $bill): string
    {
        $bill->loadMissing(["account.user", "payment"]);

        $html = View::make("pdf.bill", ["bill" => $bill])->render();

        $outputPath = storage_path("app/temp-bill-".$bill->id."-".uniqid().".pdf");

        $browsershot = Browsershot::html($html)->format("A4")->margins(0, 0, 0, 0);

        $nodeBinary = config("services.browsershot.node_binary");
        if ($nodeBinary) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        $browsershot->save($outputPath);

        return $outputPath;
    }
}
