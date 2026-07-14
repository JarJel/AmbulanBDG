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
        Schema::create('ambulances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->string('plate_number')->unique();
            $table->string('category'); // e.g., 'emergency', 'non-emergency', 'corpse'
            $table->string('status')->default('offline'); // 'offline', 'standby', 'on_duty', 'suspended'
            $table->foreignId('current_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_nurse_id')->nullable()->constrained('users')->nullOnDelete();
            $table->geometry('current_location');
            $table->timestamps();

            $table->spatialIndex('current_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambulances');
    }
};
