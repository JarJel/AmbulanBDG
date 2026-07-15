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
        // 1. Create rate_histories table
        Schema::create('rate_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambulance_id')->constrained('ambulances')->cascadeOnDelete();
            $table->decimal('old_base_fare', 12, 2);
            $table->decimal('new_base_fare', 12, 2);
            $table->decimal('old_price_per_km', 12, 2);
            $table->decimal('new_price_per_km', 12, 2);
            $table->foreignId('changed_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // 2. Create document_verifications table
        Schema::create('document_verifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('verifiable_id');
            $table->string('verifiable_type');
            $table->string('document_type'); // e.g. STNK, KIR, SIM, STR
            $table->string('file_path');
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('pending'); // pending, verified, rejected
            $table->text('rejection_notes')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['verifiable_id', 'verifiable_type']);
        });

        // 3. Create approval_logs table
        Schema::create('approval_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action_type'); // approve, reject
            $table->unsignedBigInteger('target_id');
            $table->string('target_type');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_id', 'target_type']);
        });

        // 4. Create vehicle_checklists table
        Schema::create('vehicle_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambulance_id')->constrained('ambulances')->cascadeOnDelete();
            $table->foreignId('driver_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('fuel_status'); // e.g., fully, half, low
            $table->string('oxygen_status'); // e.g., fully, half, low, none
            $table->string('medical_supplies_status'); // e.g., complete, incomplete
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 5. Update ambulances table
        Schema::table('ambulances', function (Blueprint $table) {
            $table->string('brand')->nullable();
            $table->integer('year')->nullable();
            $table->integer('capacity')->default(1);
            $table->boolean('is_draft')->default(false);
            $table->string('verification_status')->default('pending_verification'); // pending_verification, verified, rejected
        });

        // 6. Update users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 16)->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->string('invitation_token')->nullable();
        });

        // 7. Update audit_logs table to support polymorphic properties
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('causer_id')->nullable()->index();
            $table->string('causer_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->string('subject_type')->nullable();
            $table->json('properties')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['causer_id', 'causer_type', 'subject_id', 'subject_type', 'properties']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'deactivated_at', 'invitation_token']);
        });

        Schema::table('ambulances', function (Blueprint $table) {
            $table->dropColumn(['brand', 'year', 'capacity', 'is_draft', 'verification_status']);
        });

        Schema::dropIfExists('vehicle_checklists');
        Schema::dropIfExists('approval_logs');
        Schema::dropIfExists('document_verifications');
        Schema::dropIfExists('rate_histories');
    }
};
