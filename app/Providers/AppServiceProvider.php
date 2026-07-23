<?php

namespace App\Providers;

use App\Models\Bill;
use App\Models\Complaint;
use App\Models\Outage;
use App\Models\WorkOrder;
use App\Observers\BillObserver;
use App\Observers\ComplaintObserver;
use App\Observers\OutageObserver;
use App\Observers\WorkOrderObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        RateLimiter::for("submissions", function ($request) {
            return Limit::perMinutes(
                config("utility.submission_rate_window_minutes"),
                config("utility.submission_rate_limit"),
            )->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for("ai-chat", function ($request) {
            return Limit::perMinutes(
                config("utility.ai_chat_rate_window_minutes"),
                config("utility.ai_chat_rate_limit"),
            )->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for("payments", function ($request) {
            return Limit::perMinutes(
                config("utility.payments_rate_window_minutes"),
                config("utility.payments_rate_limit"),
            )->by($request->user()?->id ?: $request->ip());
        });

        Bill::observe(BillObserver::class);
        Complaint::observe(ComplaintObserver::class);
        WorkOrder::observe(WorkOrderObserver::class);
        Outage::observe(OutageObserver::class);
    }
}
