<?php

namespace Tests\Feature;

use App\Models\User;
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
}
