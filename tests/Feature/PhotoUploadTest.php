<?php

use App\Enums\AccountStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Account;
use App\Models\LeakReport;
use App\Models\Photo;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it("attaches an uploaded photo to a leak report", function () {
    Storage::fake("local");

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $response = $this->actingAs($customer)->post("/customer/leak-reports", [
        "severity" => "high",
        "zone" => $account->zone,
        "description" => "Burst pipe flooding the yard.",
        "photos" => [UploadedFile::fake()->image("leak.jpg", 100, 100)->size(500)],
    ]);

    $response->assertRedirect(route("customer.leak-reports.index"));

    $leakReport = LeakReport::first();
    expect($leakReport->photos)->toHaveCount(1);

    $photo = $leakReport->photos->first();
    Storage::disk("local")->assertExists($photo->path);
    expect($photo->uploaded_by)->toBe($customer->id);
});

it("rejects more photos than the configured maximum", function () {
    Storage::fake("local");

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $max = (int) config("utility.max_photos_per_upload");
    $files = collect(range(1, $max + 1))->map(fn ($i) => UploadedFile::fake()->image("leak{$i}.jpg")->size(100))->all();

    $response = $this->actingAs($customer)->post("/customer/leak-reports", [
        "severity" => "low",
        "zone" => $account->zone,
        "description" => "Too many photos.",
        "photos" => $files,
    ]);

    $response->assertSessionHasErrors("photos");
    $this->assertDatabaseCount("leak_reports", 0);
});

it("rejects a non-image file as a photo upload", function () {
    Storage::fake("local");

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $response = $this->actingAs($customer)->post("/customer/service-requests", [
        "type" => config("utility.service_request_types")[0],
        "zone" => $account->zone,
        "description" => "Attaching a PDF instead of a photo.",
        "photos" => [UploadedFile::fake()->create("not-a-photo.pdf", 100, "application/pdf")],
    ]);

    $response->assertSessionHasErrors("photos.0");
    $this->assertDatabaseCount("service_requests", 0);
});

it("attaches repair-evidence photos when a technician completes a work order", function () {
    Storage::fake("local");

    $technician = User::factory()->create();
    $technician->assignRole(config("roles.technician"));

    $account = Account::factory()->create(["status" => AccountStatus::Defaulted]);
    $workOrder = WorkOrder::factory()->create([
        "account_id" => $account->id,
        "status" => WorkOrderStatus::Approved,
    ]);

    $this->actingAs($technician)->patch("/technician/work-orders/{$workOrder->id}/claim")->assertRedirect();

    $response = $this->actingAs($technician)->patch("/technician/work-orders/{$workOrder->id}/complete", [
        "resolution_notes" => "Fixed the connection.",
        "photos" => [UploadedFile::fake()->image("evidence.jpg")->size(300)],
    ]);

    $response->assertRedirect(route("technician.work-orders.index"));

    expect($workOrder->fresh()->photos)->toHaveCount(1);
    expect($workOrder->fresh()->photos->first()->uploaded_by)->toBe($technician->id);
});

it("stores and serves photos from whichever disk is configured", function () {
    Storage::fake("s3");
    config(["utility.photo_disk" => "s3"]);

    $customer = User::factory()->create();
    $customer->assignRole(config("roles.customer"));
    $account = Account::factory()->create(["user_id" => $customer->id]);

    $response = $this->actingAs($customer)->post("/customer/leak-reports", [
        "severity" => "high",
        "zone" => $account->zone,
        "description" => "Burst pipe flooding the yard.",
        "photos" => [UploadedFile::fake()->image("leak.jpg", 100, 100)->size(500)],
    ]);

    $response->assertRedirect(route("customer.leak-reports.index"));

    $photo = LeakReport::first()->photos->first();
    Storage::disk("s3")->assertExists($photo->path);

    $this->actingAs($customer)->get("/photos/{$photo->id}")->assertOk();
});

it("lets the reporting customer and staff view a photo, but blocks other customers", function () {
    Storage::fake("local");

    $owner = User::factory()->create();
    $owner->assignRole(config("roles.customer"));

    $otherCustomer = User::factory()->create();
    $otherCustomer->assignRole(config("roles.customer"));

    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $account = Account::factory()->create(["user_id" => $owner->id]);
    $serviceRequest = ServiceRequest::factory()->create(["account_id" => $account->id, "requested_by" => $owner->id]);

    $photo = Photo::create([
        "photoable_type" => ServiceRequest::class,
        "photoable_id" => $serviceRequest->id,
        "uploaded_by" => $owner->id,
        "path" => "photos/fake.jpg",
        "original_filename" => "fake.jpg",
        "mime_type" => "image/jpeg",
        "size" => 100,
    ]);

    Storage::disk("local")->put("photos/fake.jpg", "fake-image-content");

    $this->actingAs($owner)->get("/photos/{$photo->id}")->assertOk();
    $this->actingAs($admin)->get("/photos/{$photo->id}")->assertOk();
    $this->actingAs($otherCustomer)->get("/photos/{$photo->id}")->assertForbidden();
});
