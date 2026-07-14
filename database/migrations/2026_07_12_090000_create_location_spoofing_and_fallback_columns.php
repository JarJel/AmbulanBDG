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
        Schema::table('ambulances', function (Blueprint $table) {
            $table->timestamp('last_location_at')->nullable()->after('current_location');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('tracking_lost_notified_at')->nullable()->after('bpjs_status');
        });

        Schema::create('location_anomalies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambulance_id')->constrained('ambulances')->cascadeOnDelete();
            $table->decimal('reported_lat', 10, 8);
            $table->decimal('reported_lng', 11, 8);
            $table->timestamp('flagged_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_anomalies');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('tracking_lost_notified_at');
        });

        Schema::table('ambulances', function (Blueprint $table) {
            $table->dropColumn('last_location_at');
        });
    }
};
