<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Bill;
use App\Services\BillPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BillController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize("viewAny", Bill::class);

        $accountIds = Account::where("user_id", $request->user()->id)->pluck("id");

        $bills = Bill::whereIn("account_id", $accountIds)
            ->with(["account", "payment"])
            ->orderByDesc("created_at")
            ->get()
            ->map(function (Bill $bill) {
                return [
                    "id" => $bill->id,
                    "account_number" => $bill->account->account_number,
                    "units_consumed" => $bill->units_consumed,
                    "amount" => $bill->amount,
                    "status" => $bill->status->value,
                    "status_label" => $bill->status->label(),
                    "due_date" => $bill->due_date->toDateString(),
                    "created_at" => $bill->created_at->toDateString(),
                    "payment_id" => $bill->payment?->id,
                ];
            });

        return Inertia::render("Customer/Bills/Index", [
            "bills" => $bills,
        ]);
    }

    public function adminIndex(Request $request): Response
    {
        if (! $request->user()->hasAnyRole([config("roles.admin"), config("roles.management")])) {
            abort(403);
        }

        $bills = Bill::with(["account.user", "payment"])
            ->orderByDesc("created_at")
            ->paginate(20)
            ->withQueryString()
            ->through(function (Bill $bill) {
                return [
                    "id" => $bill->id,
                    "customer_name" => $bill->account->user->name,
                    "account_number" => $bill->account->account_number,
                    "phone" => $bill->account->phone,
                    "units_consumed" => $bill->units_consumed,
                    "amount" => $bill->amount,
                    "status" => $bill->status->value,
                    "status_label" => $bill->status->label(),
                    "due_date" => $bill->due_date->toDateString(),
                    "is_paid" => (bool) $bill->payment,
                    "payment_id" => $bill->payment?->id,
                ];
            });

        return Inertia::render("Admin/Bills/Index", [
            "bills" => $bills,
        ]);
    }

    public function downloadPdf(Request $request, Bill $bill): BinaryFileResponse
    {
        Gate::authorize("view", $bill);

        $path = BillPdfService::generate($bill);

        return response()->download($path, "aquameter-bill-{$bill->id}.pdf")->deleteFileAfterSend();
    }
}
