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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ambulance_id')->nullable()->constrained('ambulances')->nullOnDelete();
            $table->string('order_type'); // 'SOS', 'sewa', 'event'
            $table->string('status')->default('pending'); // e.g. 'pending', 'confirmed', 'on_the_way', 'arrived', 'transporting', 'completed', 'cancelled'
            $table->dateTime('scheduled_start_time')->nullable();
            $table->dateTime('scheduled_end_time')->nullable();
            $table->geometry('pickup_location');
            $table->text('pickup_address');
            $table->geometry('destination_location')->nullable();
            $table->text('destination_address')->nullable();
            $table->decimal('fare_amount', 12, 2)->default(0.00);
            $table->string('bpjs_number')->nullable();
            $table->string('bpjs_status')->nullable(); // 'verified', 'not_verified', 'rejected'
            $table->timestamps();

            $table->spatialIndex('pickup_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
