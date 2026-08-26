<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Services\OtpDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_nip_password_login_page(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Login Absensi');
        $response->assertSee('NIP');
        $response->assertSee('Password');
    }

    public function test_employee_can_access_attendance_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'employee@example.com',
            'role' => 'employee',
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200);
        $response->assertDontSee('id="nav-admin"', false);
        $response->assertDontSee('js/admin.js');

        $this->get('/admin')->assertForbidden();
    }

    public function test_employee_must_change_initial_password_before_accessing_dashboard(): void
    {
        $employee = Employee::create([
            'name' => 'Budi Santoso',
            'nip' => '19920801',
            'phone' => '08123456789',
            'password' => 'password123',
            'must_change_password' => true,
        ]);

        $user = User::factory()->create([
            'email' => $employee->nip . '@local',
            'name' => $employee->name,
            'role' => 'employee',
        ]);

        $this->actingAs($user);

        $this->get('/attendance')
            ->assertRedirect(route('password.force'));

        $this->get(route('password.force'))
            ->assertStatus(200)
            ->assertSee('Buat Password Baru');
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->actingAs($user);

        $response = $this->get('/admin');

        $response->assertStatus(200);
    }

    public function test_forgot_password_code_is_sent_via_whatsapp_without_exposing_the_code(): void
    {
        $employee = Employee::create([
            'name' => 'Budi',
            'nip' => 'EMP001',
            'phone' => '08123456789',
            'password' => 'secret123',
        ]);

        config()->set('services.twilio.sid', 'AC123');
        config()->set('services.twilio.token', 'token123');
        config()->set('services.twilio.channel', 'whatsapp');
        config()->set('services.twilio.whatsapp_from', '+14155238886');
        config()->set('services.twilio.from', null);

        $this->partialMock(OtpDeliveryService::class, function ($mock) use ($employee) {
            $mock->shouldReceive('send')
                ->once()
                ->with($employee->phone, \Mockery::type('string'));
        });

        $response = $this->post(route('password.send-code'), [
            'phone' => '0812-3456-789',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Kode verifikasi berhasil dikirim ke nomor terdaftar.');
        $response->assertSessionMissing('verification_code');
        $this->assertNotNull(session('forgot_password_otp'));
    }

    public function test_admin_can_reset_employee_password_and_force_next_login_change(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $employee = Employee::create([
            'name' => 'Siti Rahma',
            'nip' => '19950412',
            'phone' => '08129876543',
            'password' => 'oldpassword',
            'must_change_password' => false,
        ]);

        $this->actingAs($admin);

        $response = $this->postJson("/api/employees/{$employee->id}/reset-password");

        $response->assertOk()
            ->assertJsonStructure(['message', 'temporary_password', 'employee']);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'must_change_password' => true,
        ]);
    }

    public function test_admin_created_employee_uses_birth_date_as_initial_password(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->actingAs($admin);

        $response = $this->postJson('/api/employees', [
            'name' => 'Rina Putri',
            'nip' => '20011231',
            'birth_date' => '2001-12-31',
        ]);

        $response->assertCreated();

        $employee = Employee::where('nip', '20011231')->firstOrFail();

        $this->post(route('login.post'), [
            'nip' => '20011231',
            'password' => '31122001',
        ])
            ->assertRedirect(route('password.force'));
    }

    public function test_employee_password_status_endpoint_reports_force_change_state(): void
    {
        $employee = Employee::create([
            'name' => 'Dina',
            'nip' => '19970115',
            'phone' => '08120000001',
            'password' => 'temp12345',
            'must_change_password' => true,
        ]);

        $user = User::factory()->create([
            'email' => $employee->nip . '@local',
            'name' => $employee->name,
            'role' => 'employee',
        ]);

        $response = $this->actingAs($user)->getJson(route('employee.password.status'));

        $response->assertOk()
            ->assertJson([
                'role' => 'employee',
                'must_change_password' => true,
            ])
            ->assertJsonStructure(['role', 'must_change_password', 'redirect_url']);
    }
}
