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
            if (!Schema::hasColumn('ambulances', 'is_free_service_eligible')) {
                $table->boolean('is_free_service_eligible')->default(true)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ambulances', function (Blueprint $table) {
            $table->dropColumn('is_free_service_eligible');
        });
    }
};
