<?php

namespace App\Services;

use App\Enums\ChatRole;
use App\Enums\ComplaintStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Account;
use App\Models\Bill;
use App\Models\ChatMessage;
use App\Models\Complaint;
use App\Models\LeakReport;
use App\Models\MeterReading;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Throwable;

class AiChatService
{
    protected const HISTORY_LIMIT = 10;

    protected const OPEN_COMPLAINT_STATUSES = [
        ComplaintStatus::Submitted,
        ComplaintStatus::UnderReview,
        ComplaintStatus::Approved,
    ];

    /**
     * Stores the customer's message, asks the model for a reply grounded
     * in their real account data, and stores + returns the assistant's
     * response. Never throws — a missing key or a provider failure both
     * resolve to a stored, friendly fallback message instead.
     */
    public static function reply(User $user, string $message): ChatMessage
    {
        ChatMessage::create([
            "user_id" => $user->id,
            "role" => ChatRole::User,
            "content" => $message,
        ]);

        if (blank(config("openai.api_key"))) {
            return self::storeAssistantReply(
                $user,
                "The AI assistant isn't configured yet — an admin needs to add an OpenAI API key before I can help.",
            );
        }

        try {
            $response = OpenAI::chat()->create([
                "model" => config("utility.ai_chat_model"),
                "messages" => self::buildMessages($user),
            ]);

            $content = trim((string) ($response->choices[0]->message->content ?? ""));

            if ($content === "") {
                $content = "Sorry, I didn't catch that — could you rephrase your question?";
            }
        } catch (Throwable $e) {
            Log::error("AI chat request failed", ["user_id" => $user->id, "exception" => $e->getMessage()]);
            $content = "Sorry, I couldn't process that right now. Please try again in a moment.";
        }

        return self::storeAssistantReply($user, $content);
    }

    protected static function storeAssistantReply(User $user, string $content): ChatMessage
    {
        return ChatMessage::create([
            "user_id" => $user->id,
            "role" => ChatRole::Assistant,
            "content" => $content,
        ]);
    }

    protected static function buildMessages(User $user): array
    {
        $messages = [
            ["role" => "system", "content" => self::systemPrompt($user)],
        ];

        $history = ChatMessage::where("user_id", $user->id)
            ->orderByDesc("created_at")
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->reverse();

        foreach ($history as $entry) {
            $messages[] = ["role" => $entry->role->value, "content" => $entry->content];
        }

        return $messages;
    }

    protected static function systemPrompt(User $user): string
    {
        if (self::isAdmin($user)) {
            return implode("\n", [
                "You are \"Ask Aquameter\", the internal triage assistant for water utility staff.",
                "Answer only using the operational data provided below — never invent complaints, work orders, or statuses.",
                "You can only explain and summarize data. You cannot perform actions (you cannot approve a complaint, assign a technician, change a status, etc.) — if the admin asks for an action, tell them where to do it in the app instead (e.g. \"you can approve that complaint from the Complaints page\").",
                "Help the admin prioritize — call out anything stalled or overdue, and be specific about which zone or record you mean. Keep answers short and plain-language. If something isn't in the data below, say you don't have that information rather than guessing.",
                "",
                "Operational context:",
                self::triageContext(),
            ]);
        }

        return implode("\n", [
            "You are \"Ask Aquameter\", the customer support assistant for a water utility.",
            "Answer only using the account data provided below — never invent bills, readings, or statuses.",
            "You can only explain and summarize data. You cannot perform actions (you cannot adjust a bill, dispatch a technician, change a status, etc.) — if the customer asks for an action, tell them how to do it in the app instead (e.g. \"you can report this under Leak Reports\").",
            "Keep answers short and plain-language. If something isn't in the data below, say you don't have that information rather than guessing.",
            "",
            "Account context:",
            self::accountContext($user),
        ]);
    }

    protected static function isAdmin(User $user): bool
    {
        return $user->hasRole(config("roles.admin"));
    }

    protected static function accountContext(User $user): string
    {
        $account = Account::where("user_id", $user->id)->first();

        if (! $account) {
            return "This customer has no utility account on file yet.";
        }

        $lines = [];
        $lines[] = "Account {$account->account_number}, zone {$account->zone}, status: {$account->status->label()}.";

        $bills = Bill::where("account_id", $account->id)
            ->orderByDesc("created_at")
            ->limit(3)
            ->get();

        if ($bills->isEmpty()) {
            $lines[] = "No bills yet.";
        } else {
            $lines[] = "Recent bills:";
            foreach ($bills as $bill) {
                $lines[] = "- KES {$bill->amount}, {$bill->units_consumed} units, due {$bill->due_date->toDateString()}, status: {$bill->status->label()}.";
            }
        }

        $recentReadings = MeterReading::whereHas("meter", fn ($query) => $query->where("account_id", $account->id))
            ->with("meter")
            ->orderByDesc("reading_date")
            ->limit(5)
            ->get();

        if ($recentReadings->isNotEmpty()) {
            $latestReading = $recentReadings->first();
            $lines[] = "Latest meter reading: {$latestReading->reading_value} on {$latestReading->reading_date->toDateString()}"
                . ($latestReading->is_anomalous ? " (flagged as unusual — see below)." : ".");

            foreach ($recentReadings->where("is_anomalous", true) as $anomalousReading) {
                $lines[] = "Anomaly on {$anomalousReading->reading_date->toDateString()}: "
                    . MeterAnomalyDetector::explain($anomalousReading);
            }
        }

        $openLeaks = LeakReport::where("account_id", $account->id)
            ->latest()
            ->limit(3)
            ->get();
        foreach ($openLeaks as $leak) {
            $lines[] = "Leak report ({$leak->severity->label()} severity, {$leak->created_at->toDateString()}): {$leak->description}";
        }

        $openRequests = ServiceRequest::where("account_id", $account->id)
            ->latest()
            ->limit(3)
            ->get();
        foreach ($openRequests as $request) {
            $lines[] = "Service request ({$request->type}, {$request->created_at->toDateString()}): {$request->description}";
        }

        $openComplaints = Complaint::where("account_id", $account->id)
            ->latest()
            ->limit(3)
            ->get();
        foreach ($openComplaints as $complaint) {
            $lines[] = "Complaint \"{$complaint->subject}\" — status: {$complaint->status->label()}.";
        }

        return implode("\n", $lines);
    }

    protected static function triageContext(): string
    {
        $stallDays = (int) config("utility.stall_threshold_days");
        $stallThreshold = now()->subDays($stallDays);

        $openComplaintsQuery = Complaint::whereIn("status", self::OPEN_COMPLAINT_STATUSES);
        $stalledComplaints = (clone $openComplaintsQuery)->where("created_at", "<=", $stallThreshold)->count();

        $openWorkOrdersQuery = WorkOrder::whereNotIn("status", [WorkOrderStatus::Completed, WorkOrderStatus::Cancelled]);
        $stalledWorkOrders = WorkOrder::where("status", WorkOrderStatus::Approved)
            ->where("created_at", "<=", $stallThreshold)
            ->count();

        $lines = [];
        $lines[] = "Open complaints: {$openComplaintsQuery->count()} ({$stalledComplaints} open longer than {$stallDays} days).";

        $recentComplaints = (clone $openComplaintsQuery)->with("account")->latest()->limit(5)->get();
        foreach ($recentComplaints as $complaint) {
            $stalled = $complaint->created_at->lte($stallThreshold) ? " — stalled" : "";
            $zone = $complaint->account?->zone ?? "unknown zone";
            $lines[] = "- \"{$complaint->subject}\" (zone {$zone}, status: {$complaint->status->label()}, opened {$complaint->created_at->toDateString()}){$stalled}.";
        }

        $lines[] = "";
        $lines[] = "Active work order pipeline: {$openWorkOrdersQuery->count()} ({$stalledWorkOrders} unclaimed longer than {$stallDays} days).";

        $recentWorkOrders = (clone $openWorkOrdersQuery)->latest()->limit(5)->get();
        foreach ($recentWorkOrders as $order) {
            $overdue = $order->notice_deadline && $order->notice_deadline->isPast() ? " — notice deadline passed" : "";
            $zone = $order->dispatchZone() ?? "unknown zone";
            $lines[] = "- {$order->type->label()} in zone {$zone}, status: {$order->status->label()}, opened {$order->created_at->toDateString()}{$overdue}.";
        }

        $byZone = (clone $openWorkOrdersQuery)->get()->groupBy(fn (WorkOrder $order) => $order->dispatchZone() ?? "unknown zone")->map->count();
        if ($byZone->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "Open work orders by zone: ".$byZone->map(fn ($count, $zone) => "{$zone}: {$count}")->implode(", ").".";
        }

        return implode("\n", $lines);
    }
}
