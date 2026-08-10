<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('employees', 'pin')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('pin');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('employees', 'pin')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('pin', 4)->nullable();
            });
        }
    }
};
