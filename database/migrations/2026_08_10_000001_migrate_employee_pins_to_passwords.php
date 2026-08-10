<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employees')->whereNull('password')->orderBy('id')->each(function ($employee) {
            DB::table('employees')->where('id', $employee->id)->update([
                'password' => Hash::make($employee->pin),
            ]);
        });
    }

    public function down(): void
    {
        // Password yang sudah di-hash tidak dapat dikembalikan ke PIN semula.
    }
};
