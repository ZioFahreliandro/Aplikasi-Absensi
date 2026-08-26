<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed default settings if empty
        if (Setting::count() === 0) {
            Setting::create([
                'office_name' => 'Kantor Pusat',
                'office_lat' => -6.200000,
                'office_lng' => 106.816666,
                'office_radius' => 100, // in meters
                'office_ip' => '127.0.0.1',
                'enable_gps' => false, // disabled by default for testing convenience
                'enable_ip' => false  // disabled by default for testing convenience
            ]);
        }

        // Seed default employees if empty
        if (Employee::count() === 0) {
            Employee::create([
                'name' => 'Budi Santoso',
                'nip' => '19920801',
                'password' => 'password123',
                'must_change_password' => true,
            ]);
            Employee::create([
                'name' => 'Siti Rahma',
                'nip' => '19950412',
                'password' => 'password123',
                'must_change_password' => true,
            ]);
            Employee::create([
                'name' => 'Joko Widodo',
                'nip' => '19901130',
                'password' => 'password123',
                'must_change_password' => true,
            ]);
        }
    }
}
