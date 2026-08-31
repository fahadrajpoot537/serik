<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('re_appointment_bookings')) {
            return;
        }

        Schema::create('re_appointment_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('public_token', 64)->unique();
            $table->string('booking_reference', 32)->unique();
            $table->string('idempotency_key', 64)->unique();
            $table->string('slot_key', 48)->unique();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('status', 24)->default('pending');
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('phone', 32);
            $table->string('consultation_type', 64);
            $table->date('appointment_date');
            $table->string('appointment_time', 16);
            $table->string('timezone', 64)->default('America/Toronto');
            $table->string('source', 128)->default('Serik.ca - Appointment');
            $table->string('submitted_page', 255)->nullable();
            $table->string('property_url', 255)->nullable();
            $table->string('assigned_recipient', 255)->nullable();
            $table->string('ghl_contact_id', 64)->nullable();
            $table->string('calendar_event_id', 64)->nullable();
            $table->string('client_mail_id', 128)->nullable();
            $table->string('team_mail_id', 128)->nullable();
            $table->timestamp('client_mail_sent_at')->nullable();
            $table->timestamp('team_mail_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('failed_step', 32)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('last_attempted_at')->nullable();
            $table->json('steps')->nullable();
            $table->timestamps();

            $table->index(['status', 'appointment_date']);
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('re_appointment_bookings');
    }
};
