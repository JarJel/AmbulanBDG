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
        Schema::table('sos_logs', function (Blueprint $table) {
            $table->foreignId('ambulance_id')->nullable()->after('order_id')->constrained('ambulances')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable()->after('ambulance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sos_logs', function (Blueprint $table) {
            $table->dropForeign(['ambulance_id']);
            $table->dropColumn(['ambulance_id', 'dispatched_at']);
        });
    }
};
