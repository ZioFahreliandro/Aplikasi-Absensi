<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'phone')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('phone')->nullable()->unique()->after('nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('employees', 'phone')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_phone_unique');
            $table->dropColumn('phone');
        });
    }
};
