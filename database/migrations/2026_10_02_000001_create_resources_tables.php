<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resource_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('active')->index();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('image_path')->nullable();
            $table->string('approval_mode')->default('none');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('booking_rules')->nullable();
            $table->time('available_from')->nullable();
            $table->time('available_to')->nullable();
            $table->json('available_days')->nullable();
            $table->boolean('is_bookable')->default(true);
            $table->json('custom_fields')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'resource_type_id'], 'resources_status_type_index');
        });

        Schema::create('resource_blocked_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('blocked');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['resource_id', 'starts_at', 'ends_at'], 'blocked_periods_resource_window_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_blocked_periods');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('resource_types');
    }
};
