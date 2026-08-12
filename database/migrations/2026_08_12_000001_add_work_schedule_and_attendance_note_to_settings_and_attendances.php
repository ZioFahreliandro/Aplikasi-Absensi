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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('office_checkin_time')->default('08:00:00')->after('office_radius');
            $table->string('office_checkout_time')->default('17:00:00')->after('office_checkin_time');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->string('attendance_note')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('attendance_note');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['office_checkin_time', 'office_checkout_time']);
        });
    }
};
