<?php

use App\Models\PipeSegment;
use App\Models\User;

function validPipeSegmentPayload(): array
{
    $bounds = config("utility.service_area_bounds");
    $zone = config("utility.zones")[0];

    return [
        "name" => "Test Main Line",
        "zone" => $zone,
        "status" => "active",
        "points" => [
            ["lat" => $bounds["min_lat"] + 0.01, "lng" => $bounds["min_lng"] + 0.01],
            ["lat" => $bounds["min_lat"] + 0.02, "lng" => $bounds["min_lng"] + 0.02],
        ],
    ];
}

it("allows an admin to create a pipe segment", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $response = $this->actingAs($admin)->post("/admin/pipe-segments", validPipeSegmentPayload());

    $response->assertRedirect(route("admin.pipe-segments.index"));
    $this->assertDatabaseHas("pipe_segments", [
        "name" => "Test Main Line",
        "created_by" => $admin->id,
    ]);
});

it("rejects a pipe segment with fewer than two points", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $payload = validPipeSegmentPayload();
    $payload["points"] = [$payload["points"][0]];

    $response = $this->actingAs($admin)->post("/admin/pipe-segments", $payload);

    $response->assertSessionHasErrors("points");
    $this->assertDatabaseCount("pipe_segments", 0);
});

it("rejects a pipe segment with a point outside the service area", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $payload = validPipeSegmentPayload();
    $payload["points"][1] = ["lat" => 40.7128, "lng" => -74.0060];

    $response = $this->actingAs($admin)->post("/admin/pipe-segments", $payload);

    $response->assertSessionHasErrors();
    $this->assertDatabaseCount("pipe_segments", 0);
});

it("prevents non-admins from managing pipe segments", function () {
    foreach (["customer", "technician", "management"] as $role) {
        $user = User::factory()->create();
        $user->assignRole(config("roles.$role"));

        $this->actingAs($user)->get("/admin/pipe-segments")->assertForbidden();
        $this->actingAs($user)->post("/admin/pipe-segments", validPipeSegmentPayload())->assertForbidden();
    }
});

it("allows an admin to update a pipe segment's status", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $segment = PipeSegment::create(array_merge(validPipeSegmentPayload(), ["created_by" => $admin->id]));

    $this->actingAs($admin)
        ->patch("/admin/pipe-segments/{$segment->id}/status", ["status" => "damaged"])
        ->assertRedirect(route("admin.pipe-segments.index"));

    expect($segment->fresh()->status->value)->toBe("damaged");
});

it("allows an admin to delete a pipe segment", function () {
    $admin = User::factory()->create();
    $admin->assignRole(config("roles.admin"));

    $segment = PipeSegment::create(array_merge(validPipeSegmentPayload(), ["created_by" => $admin->id]));

    $this->actingAs($admin)
        ->delete("/admin/pipe-segments/{$segment->id}")
        ->assertRedirect(route("admin.pipe-segments.index"));

    $this->assertDatabaseMissing("pipe_segments", ["id" => $segment->id]);
});
