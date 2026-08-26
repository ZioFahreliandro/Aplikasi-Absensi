<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceLateReasonTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_late_checkin_requires_late_reason(): void
    {
        $this->setAttendanceTime('2026-08-26 09:00:00');
        $this->seedAttendanceContext();

        $user = $this->createEmployeeUser();

        $this->actingAs($user)
            ->postJson('/api/attendance', $this->attendancePayload())
            ->assertStatus(422)
            ->assertJson([
                'error' => 'Alasan telat wajib diisi saat absen masuk terlambat.',
            ]);

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_late_checkin_saves_reason_when_filled(): void
    {
        $this->setAttendanceTime('2026-08-26 09:00:00');
        $this->seedAttendanceContext();

        $user = $this->createEmployeeUser();

        $this->actingAs($user)
            ->postJson('/api/attendance', $this->attendancePayload('Macet di jalan'))
            ->assertCreated()
            ->assertJson([
                'record' => [
                    'attendance_note' => 'Anda Telat',
                    'late_reason' => 'Macet di jalan',
                ],
            ]);

        $this->assertDatabaseHas('attendances', [
            'employee_name' => 'Budi Santoso',
            'attendance_note' => 'Anda Telat',
            'late_reason' => 'Macet di jalan',
        ]);
    }

    public function test_on_time_checkin_hides_late_reason_and_stores_null(): void
    {
        $this->setAttendanceTime('2026-08-26 07:30:00');
        $this->seedAttendanceContext();

        $user = $this->createEmployeeUser();

        $this->actingAs($user)
            ->postJson('/api/attendance', $this->attendancePayload())
            ->assertCreated()
            ->assertJson([
                'record' => [
                    'attendance_note' => 'Anda Disiplin',
                    'late_reason' => null,
                ],
            ]);

        $this->assertDatabaseHas('attendances', [
            'employee_name' => 'Budi Santoso',
            'attendance_note' => 'Anda Disiplin',
            'late_reason' => null,
        ]);
    }

    private function setAttendanceTime(string $datetime): void
    {
        Carbon::setTestNow(Carbon::createFromFormat('Y-m-d H:i:s', $datetime, 'Asia/Jakarta'));
    }

    private function seedAttendanceContext(): void
    {
        Setting::create([
            'office_name' => 'Kantor Pusat',
            'office_lat' => -6.200000,
            'office_lng' => 106.816666,
            'office_checkin_time' => '08:00:00',
            'office_checkout_time' => '17:00:00',
            'office_radius' => 100,
            'enable_gps' => false,
        ]);
    }

    private function createEmployeeUser(): User
    {
        $employee = Employee::create([
            'name' => 'Budi Santoso',
            'nip' => '19920801',
            'phone' => '08123456789',
            'birth_date' => '1992-08-01',
            'password' => 'password123',
            'must_change_password' => false,
        ]);

        return User::factory()->create([
            'email' => $employee->nip . '@local',
            'name' => $employee->name,
            'role' => 'employee',
        ]);
    }

    private function attendancePayload(string $lateReason = ''): array
    {
        return [
            'type' => 'masuk',
            'selfie' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=',
            'latitude' => -6.2,
            'longitude' => 106.8,
            'late_reason' => $lateReason,
        ];
    }
}
