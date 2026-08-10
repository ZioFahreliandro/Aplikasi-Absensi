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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_name');
            $table->date('date');
            $table->time('time');
            $table->string('type'); // 'masuk' or 'pulang'
            $table->string('selfie_url');
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->integer('distance')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('status')->default('Success');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
