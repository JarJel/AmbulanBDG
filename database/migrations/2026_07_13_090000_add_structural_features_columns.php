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
        // 1. Update institutions table
        Schema::table('institutions', function (Blueprint $table) {
            if (!Schema::hasColumn('institutions', 'license_number')) {
                $table->string('license_number')->nullable()->after('type');
            }
            if (!Schema::hasColumn('institutions', 'address')) {
                $table->string('address')->nullable()->after('verified_status');
            }
            if (!Schema::hasColumn('institutions', 'phone')) {
                $table->string('phone')->nullable()->after('address');
            }
        });

        // 2. Add reminder_sent_at to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('bpjs_status');
            }
        });

        // 3. Add alert_sent_at to vehicle_documents table
        Schema::table('vehicle_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicle_documents', 'alert_sent_at')) {
                $table->timestamp('alert_sent_at')->nullable()->after('expiry_date');
            }
        });

        // 4. Add alert_sent_at to staff_profiles table
        Schema::table('staff_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_profiles', 'alert_sent_at')) {
                $table->timestamp('alert_sent_at')->nullable()->after('verification_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['license_number', 'address', 'phone']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });

        Schema::table('vehicle_documents', function (Blueprint $table) {
            $table->dropColumn('alert_sent_at');
        });

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn('alert_sent_at');
        });
    }
};
