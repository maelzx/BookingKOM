<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_booking_created')->default(true);
            $table->boolean('notify_approval_required')->default(true);
            $table->boolean('notify_booking_decisions')->default(true);
            $table->boolean('notify_booking_reminders')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_booking_created',
                'notify_approval_required',
                'notify_booking_decisions',
                'notify_booking_reminders',
            ]);
        });

        Schema::dropIfExists('attachments');
    }
};
