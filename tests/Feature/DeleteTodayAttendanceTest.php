<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTodayAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_delete_todays_attendance_records_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        Attendance::create([
            'employee_id' => null,
            'employee_name' => 'Budi',
            'date' => $today,
            'time' => '08:00:00',
            'type' => 'masuk',
            'selfie_url' => 'uploads/selfies/test.jpg',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance' => 50,
            'ip_address' => '127.0.0.1',
            'status' => 'Success',
        ]);

        Attendance::create([
            'employee_id' => null,
            'employee_name' => 'Ani',
            'date' => $yesterday,
            'time' => '09:00:00',
            'type' => 'pulang',
            'selfie_url' => 'uploads/selfies/old.jpg',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'distance' => 60,
            'ip_address' => '127.0.0.1',
            'status' => 'Success',
        ]);

        $response = $this->deleteJson('/api/attendance/today');

        $response->assertOk()
            ->assertJsonPath('deleted', 1);

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', ['employee_name' => 'Ani']);
        $this->assertDatabaseMissing('attendances', ['employee_name' => 'Budi']);
    }
}
