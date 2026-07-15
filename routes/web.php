<?php

use App\Http\Controllers\BillController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\MeterController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
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
});

Route::middleware(["auth", "verified", "role:customer"])->prefix("customer")->name("customer.")->group(function () {
    Route::get("/dashboard", function () {
        return Inertia::render("Customer/Dashboard");
    })->name("dashboard");

    Route::get("/meter-readings", [MeterReadingController::class, "index"])->name("meter-readings.index");
    Route::get("/bills", [BillController::class, "index"])->name("bills.index");
    Route::get("/complaints", [ComplaintController::class, "index"])->name("complaints.index");
    Route::get("/complaints/create", [ComplaintController::class, "create"])->name("complaints.create");
    Route::post("/complaints", [ComplaintController::class, "store"])->name("complaints.store");
});

Route::middleware(["auth", "verified", "role:technician"])->prefix("technician")->name("technician.")->group(function () {
    Route::get("/dashboard", function () {
        return Inertia::render("Technician/Dashboard");
    })->name("dashboard");

    Route::get("/meter-readings/create", [MeterReadingController::class, "create"])->name("meter-readings.create");
    Route::post("/meter-readings", [MeterReadingController::class, "store"])->name("meter-readings.store");
});

Route::middleware(["auth", "verified", "role:admin"])->prefix("admin")->name("admin.")->group(function () {
    Route::get("/dashboard", function () {
        return Inertia::render("Admin/Dashboard");
    })->name("dashboard");

    Route::get("/meters/create", [MeterController::class, "create"])->name("meters.create");
    Route::post("/meters", [MeterController::class, "store"])->name("meters.store");

    Route::get("/complaints", [ComplaintController::class, "adminIndex"])->name("complaints.index");
    Route::get("/complaints/{complaint}", [ComplaintController::class, "show"])->name("complaints.show");
    Route::patch("/complaints/{complaint}", [ComplaintController::class, "update"])->name("complaints.update");

    Route::get("/bills", [BillController::class, "adminIndex"])->name("bills.index");
    Route::get("/bills/{bill}/payments/create", [PaymentController::class, "create"])->name("payments.create");
    Route::post("/bills/{bill}/payments", [PaymentController::class, "store"])->name("payments.store");
});

Route::middleware(["auth", "verified", "role:management"])->prefix("management")->name("management.")->group(function () {
    Route::get("/dashboard", function () {
        return Inertia::render("Management/Dashboard");
    })->name("dashboard");
});

require __DIR__."/auth.php";
