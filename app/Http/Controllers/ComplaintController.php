<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintStatus;
use App\Models\Account;
use App\Models\Bill;
use App\Models\Complaint;
use App\Models\User;
use App\Notifications\ComplaintStatusUpdated;
use App\Notifications\ComplaintSubmitted;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize("viewAny", Complaint::class);

        $accountIds = Account::where("user_id", $request->user()->id)->pluck("id");

        $complaints = Complaint::whereIn("account_id", $accountIds)
            ->with("bill")
            ->orderByDesc("created_at")
            ->get()
            ->map(function (Complaint $complaint) {
                return [
                    "id" => $complaint->id,
                    "subject" => $complaint->subject,
                    "description" => $complaint->description,
                    "status" => $complaint->status->value,
                    "status_label" => $complaint->status->label(),
                    "resolution_notes" => $complaint->resolution_notes,
                    "bill_amount" => $complaint->bill?->amount,
                    "created_at" => $complaint->created_at->toDateString(),
                ];
            });

        return Inertia::render("Customer/Complaints/Index", [
            "complaints" => $complaints,
        ]);
    }

    public function create(Request $request): Response
    {
        $account = Account::where("user_id", $request->user()->id)->firstOrFail();

        Gate::authorize("createFor", [Complaint::class, $account]);

        $bills = Bill::where("account_id", $account->id)
            ->orderByDesc("created_at")
            ->get()
            ->map(function (Bill $bill) {
                return [
                    "id" => $bill->id,
                    "amount" => $bill->amount,
                    "due_date" => $bill->due_date->toDateString(),
                    "status" => $bill->status->value,
                ];
            });

        return Inertia::render("Customer/Complaints/Create", [
            "bills" => $bills,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = Account::where("user_id", $request->user()->id)->firstOrFail();

        Gate::authorize("createFor", [Complaint::class, $account]);

        $validated = $request->validate([
            "bill_id" => "nullable|exists:bills,id",
            "subject" => "required|string|max:255",
            "description" => "required|string|max:2000",
        ]);

        if (! empty($validated["bill_id"])) {
            $bill = Bill::findOrFail($validated["bill_id"]);
            if ($bill->account_id !== $account->id) {
                abort(403);
            }
        }

        $complaint = Complaint::create([
            "account_id" => $account->id,
            "bill_id" => $validated["bill_id"] ?? null,
            "submitted_by" => $request->user()->id,
            "subject" => $validated["subject"],
            "description" => $validated["description"],
            "status" => "submitted",
        ]);

        Notification::send(User::role(config("roles.admin"))->get(), new ComplaintSubmitted($complaint));

        return redirect()
            ->route("customer.complaints.index")
            ->with("status", "Complaint submitted. You will be notified once it is reviewed.");
    }

    public function adminIndex(Request $request): Response
    {
        $user = $request->user();

        if (! $user->hasAnyRole([config("roles.admin"), config("roles.management")])) {
            abort(403);
        }

        $complaints = Complaint::with(["account.user", "bill"])
            ->orderByRaw($this->statusSortOrder())
            ->orderByDesc("created_at")
            ->get()
            ->map(function (Complaint $complaint) {
                return [
                    "id" => $complaint->id,
                    "subject" => $complaint->subject,
                    "description" => $complaint->description,
                    "status" => $complaint->status->value,
                    "status_label" => $complaint->status->label(),
                    "customer_name" => $complaint->account->user->name,
                    "account_number" => $complaint->account->account_number,
                    "phone" => $complaint->account->phone,
                    "bill_amount" => $complaint->bill?->amount,
                    "resolution_notes" => $complaint->resolution_notes,
                    "created_at" => $complaint->created_at->toDateString(),
                ];
            });

        return Inertia::render("Admin/Complaints/Index", [
            "complaints" => $complaints,
        ]);
    }

    public function show(Request $request, Complaint $complaint): Response
    {
        Gate::authorize("review", $complaint);

        $complaint->load(["account.user", "bill"]);

        return Inertia::render("Admin/Complaints/Show", [
            "complaint" => [
                "id" => $complaint->id,
                "subject" => $complaint->subject,
                "description" => $complaint->description,
                "status" => $complaint->status->value,
                "status_label" => $complaint->status->label(),
                "customer_name" => $complaint->account->user->name,
                "account_number" => $complaint->account->account_number,
                "phone" => $complaint->account->phone,
                "alternate_email" => $complaint->account->alternate_email,
                "bill_amount" => $complaint->bill?->amount,
                "resolution_notes" => $complaint->resolution_notes,
                "created_at" => $complaint->created_at->toDateString(),
            ],
        ]);
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        Gate::authorize("review", $complaint);

        $validated = $request->validate([
            "status" => ["required", Rule::in(["under_review", "approved", "resolved", "rejected"])],
            "resolution_notes" => ["required_if:status,resolved,rejected", "nullable", "string", "max:2000"],
        ]);

        $complaint->status = $validated["status"];
        $complaint->resolution_notes = $validated["resolution_notes"] ?? $complaint->resolution_notes;

        if (in_array($validated["status"], ["resolved", "rejected"])) {
            $complaint->resolved_by = $request->user()->id;
            $complaint->resolved_at = now();
        }

        $complaint->save();

        if ($validated["status"] === "approved") {
            WorkOrderService::fromComplaint($complaint, $request->user());
        }

        $complaint->submittedBy->notify(new ComplaintStatusUpdated($complaint));

        return redirect()
            ->route("admin.complaints.index")
            ->with("status", "Complaint updated.");
    }

    /**
     * Open complaints first, most recently created within each status.
     * Built from ComplaintStatus's own declared case order via a
     * portable SQL CASE expression — MySQL's FIELD() isn't available
     * on SQLite, which silently broke this query in local dev/tests.
     */
    protected function statusSortOrder(): string
    {
        $whens = collect(ComplaintStatus::cases())
            ->map(fn (ComplaintStatus $status, int $priority) => "WHEN '{$status->value}' THEN {$priority}")
            ->implode(" ");

        return "CASE status {$whens} ELSE 99 END";
    }
}
