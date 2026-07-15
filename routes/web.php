<?php

use App\Http\Controllers\BillController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeakReportController;
use App\Http\Controllers\MeterController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get("/", function () {
    return Inertia::render("Welcome", [
        "canLogin" => Route::has("login"),
        "canRegister" => Route::has("register"),
        "laravelVersion" => Application::VERSION,
        "phpVersion" => PHP_VERSION,
    ]);
});

Route::get("/dashboard", function () {
    $user = request()->user();

    return match (true) {
        $user->hasRole(config("roles.admin"))       => redirect()->route("admin.dashboard"),
        $user->hasRole(config("roles.management"))  => redirect()->route("management.dashboard"),
        $user->hasRole(config("roles.technician"))  => redirect()->route("technician.dashboard"),
        $user->hasRole(config("roles.customer"))    => redirect()->route("customer.dashboard"),
        default => Inertia::render("Dashboard"),
    };
})->middleware(["auth", "verified"])->name("dashboard");

Route::middleware("auth")->group(function () {
    Route::get("/profile", [ProfileController::class, "edit"])->name("profile.edit");
    Route::patch("/profile", [ProfileController::class, "update"])->name("profile.update");
    Route::delete("/profile", [ProfileController::class, "destroy"])->name("profile.destroy");

    Route::get("/notifications", [NotificationController::class, "index"])->name("notifications.index");
    Route::patch("/notifications/{id}/read", [NotificationController::class, "markRead"])->name("notifications.read");
    Route::patch("/notifications/read-all", [NotificationController::class, "markAllRead"])->name("notifications.read-all");

    Route::get("/bills/{bill}/pdf", [BillController::class, "downloadPdf"])->name("bills.pdf");

    Route::get("/photos/{photo}", [PhotoController::class, "show"])->name("photos.show");
});

Route::middleware(["auth", "verified", "role:customer"])->prefix("customer")->name("customer.")->group(function () {
    Route::get("/dashboard", [DashboardController::class, "customer"])->name("dashboard");

    Route::get("/meter-readings", [MeterReadingController::class, "index"])->name("meter-readings.index");
    Route::get("/bills", [BillController::class, "index"])->name("bills.index");
    Route::get("/complaints", [ComplaintController::class, "index"])->name("complaints.index");
    Route::get("/complaints/create", [ComplaintController::class, "create"])->name("complaints.create");
    Route::post("/complaints", [ComplaintController::class, "store"])->name("complaints.store");

    Route::post("/work-orders/{workOrder}/dispute", [WorkOrderController::class, "dispute"])->name("work-orders.dispute");

    Route::get("/leak-reports", [LeakReportController::class, "index"])->name("leak-reports.index");
    Route::get("/leak-reports/create", [LeakReportController::class, "create"])->name("leak-reports.create");
    Route::post("/leak-reports", [LeakReportController::class, "store"])->name("leak-reports.store");

    Route::get("/service-requests", [ServiceRequestController::class, "index"])->name("service-requests.index");
    Route::get("/service-requests/create", [ServiceRequestController::class, "create"])->name("service-requests.create");
    Route::post("/service-requests", [ServiceRequestController::class, "store"])->name("service-requests.store");
});

Route::middleware(["auth", "verified", "role:technician"])->prefix("technician")->name("technician.")->group(function () {
    Route::get("/dashboard", [DashboardController::class, "technician"])->name("dashboard");

    Route::get("/meter-readings/create", [MeterReadingController::class, "create"])->name("meter-readings.create");
    Route::post("/meter-readings", [MeterReadingController::class, "store"])->name("meter-readings.store");

    Route::get("/work-orders", [WorkOrderController::class, "technicianIndex"])->name("work-orders.index");
    Route::patch("/work-orders/{workOrder}/claim", [WorkOrderController::class, "claim"])->name("work-orders.claim");
    Route::patch("/work-orders/{workOrder}/complete", [WorkOrderController::class, "complete"])->name("work-orders.complete");
});

Route::middleware(["auth", "verified", "role:admin"])->prefix("admin")->name("admin.")->group(function () {
    Route::get("/dashboard", [DashboardController::class, "admin"])->name("dashboard");

    Route::get("/meters/create", [MeterController::class, "create"])->name("meters.create");
    Route::post("/meters", [MeterController::class, "store"])->name("meters.store");

    Route::get("/complaints", [ComplaintController::class, "adminIndex"])->name("complaints.index");
    Route::get("/complaints/{complaint}", [ComplaintController::class, "show"])->name("complaints.show");
    Route::patch("/complaints/{complaint}", [ComplaintController::class, "update"])->name("complaints.update");

    Route::get("/bills", [BillController::class, "adminIndex"])->name("bills.index");
    Route::get("/bills/{bill}/payments/create", [PaymentController::class, "create"])->name("payments.create");
    Route::post("/bills/{bill}/payments", [PaymentController::class, "store"])->name("payments.store");

    Route::get("/work-orders", [WorkOrderController::class, "adminIndex"])->name("work-orders.index");
    Route::post("/accounts/{account}/disconnection-notice", [WorkOrderController::class, "initiateDisconnection"])->name("work-orders.initiate-disconnection");
    Route::patch("/work-orders/{workOrder}/sign-off", [WorkOrderController::class, "signOff"])->name("work-orders.sign-off");
    Route::patch("/work-orders/{workOrder}/cancel", [WorkOrderController::class, "cancel"])->name("work-orders.cancel");
    Route::patch("/work-orders/{workOrder}/resolve-dispute", [WorkOrderController::class, "resolveDispute"])->name("work-orders.resolve-dispute");

    Route::get("/technicians", [TechnicianController::class, "index"])->name("technicians.index");
    Route::patch("/technicians/{technician}/zone", [TechnicianController::class, "updateZone"])->name("technicians.update-zone");
});

Route::middleware(["auth", "verified", "role:management"])->prefix("management")->name("management.")->group(function () {
    Route::get("/dashboard", function () {
        return Inertia::render("Management/Dashboard");
    })->name("dashboard");
});

require __DIR__."/auth.php";
