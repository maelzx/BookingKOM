<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('title');
            $table->text('purpose')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('department')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('status')->default('pending')->index();
            $table->json('recurrence_rule')->nullable();
            $table->foreignId('recurrence_parent_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->unsignedSmallInteger('occurrence_index')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('decision_note')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancel_reason')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('no_show_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at'], 'bookings_status_starts_index');
            $table->index(['starts_at', 'ends_at'], 'bookings_window_index');
            $table->index(['user_id', 'starts_at'], 'bookings_user_starts_index');
        });

        Schema::create('booking_resource', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('responded_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'resource_id']);
            $table->index(['resource_id', 'status'], 'booking_resource_resource_status_index');
        });

        Schema::create('booking_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_organiser')->default(false);
            $table->timestamps();

            $table->index(['booking_id', 'user_id'], 'booking_attendees_booking_user_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_attendees');
        Schema::dropIfExists('booking_resource');
        Schema::dropIfExists('bookings');
    }
};
