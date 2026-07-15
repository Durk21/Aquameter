<?php

namespace App\Console\Commands;

use App\Enums\AccountStatus;
use App\Enums\BillStatus;
use App\Models\Bill;
use Illuminate\Console\Command;

class DetectBillDefaults extends Command
{
    protected $signature = "bills:detect-defaults";

    protected $description = "Marks overdue bills as overdue/defaulted based on config-driven grace periods, and mirrors the change onto the owning account.";

    public function handle(): int
    {
        $overdueCount = $this->markOverdue();
        $defaultedCount = $this->markDefaulted();

        $this->info("Marked {$overdueCount} bill(s) overdue and {$defaultedCount} bill(s) defaulted.");

        return self::SUCCESS;
    }

    protected function markOverdue(): int
    {
        $bills = Bill::where("status", BillStatus::Pending)
            ->whereDate("due_date", "<", now()->toDateString())
            ->with("account")
            ->get();

        foreach ($bills as $bill) {
            $bill->status = BillStatus::Overdue;
            $bill->save();

            $account = $bill->account;

            if ($account->status === AccountStatus::Active) {
                $account->status = AccountStatus::Overdue;
                $account->save();
            }
        }

        return $bills->count();
    }

    protected function markDefaulted(): int
    {
        $graceDays = (int) config("utility.default_grace_days");

        $bills = Bill::where("status", BillStatus::Overdue)
            ->whereDate("due_date", "<", now()->subDays($graceDays)->toDateString())
            ->with("account")
            ->get();

        foreach ($bills as $bill) {
            $bill->status = BillStatus::Defaulted;
            $bill->save();

            $account = $bill->account;

            if (in_array($account->status, [AccountStatus::Active, AccountStatus::Overdue], true)) {
                $account->status = AccountStatus::Defaulted;
                $account->defaulted_at = $account->defaulted_at ?? now();
                $account->save();
            }
        }

        return $bills->count();
    }
}
