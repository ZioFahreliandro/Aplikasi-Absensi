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
            'password' => bcrypt('secret123'),
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
}
